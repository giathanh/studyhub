<?php

namespace App\Actions\Courses;

use App\Models\Lesson;
use App\Models\User;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReadGithubCourse
{
    private const int MAX_BYTES = 7 * 1024 * 1024;

    /** @return array{token: string, title: string, files: list<array{path: string, size: int}>} */
    public function preview(User $user, string $url): array
    {
        $repository = preg_replace('/\.git$/', '', substr(rtrim($url, '/'), strlen('https://github.com/')));
        $metadata = $this->api("repos/{$repository}");
        $branch = rawurlencode($metadata['default_branch'] ?? 'main');
        $commit = $this->api("repos/{$repository}/commits/{$branch}")['sha'] ?? '';

        if (! is_string($commit) || ! preg_match('/\A[a-f0-9]{40}\z/', $commit)) {
            $this->fail('repository_url', 'GitHub returned an invalid repository version.');
        }

        $tree = $this->api("repos/{$repository}/git/trees/{$commit}?recursive=1");

        if ($tree['truncated'] ?? false) {
            $this->fail('repository_url', 'This repository is too large to list completely. Import a local folder instead.');
        }

        $files = collect($tree['tree'] ?? [])
            ->filter(fn (array $file): bool => ($file['type'] ?? '') === 'blob'
                && in_array($file['mode'] ?? '', ['100644', '100755'])
                && $this->isMarkdown($file['path']))
            ->map(fn (array $file): array => ['path' => $file['path'], 'size' => (int) ($file['size'] ?? 0)])
            ->sort(fn (array $a, array $b): int => strnatcasecmp($a['path'], $b['path']))
            ->values()->all();

        if ($files === []) {
            $this->fail('repository_url', 'No Markdown files were found in this repository.');
        }

        if (count($files) > 500) {
            $this->fail('repository_url', 'This repository has more than 500 Markdown files. Import a local folder instead.');
        }

        $token = Str::random(64);
        Cache::put($this->key($user, $token), compact('repository', 'commit', 'files'), now()->addMinutes(30));

        return ['token' => $token, 'title' => Str::headline(basename($repository)), 'files' => $files];
    }

    /**
     * @param  list<string>  $paths
     * @return list<array{path: string, content: string}>
     */
    public function files(User $user, string $token, array $paths): array
    {
        $preview = Cache::get($this->key($user, $token));

        if ($preview === null) {
            $this->fail('token', 'This preview has expired. Load the repository again.');
        }

        $available = array_column($preview['files'], 'size', 'path');
        $total = 0;

        foreach ($paths as $path) {
            if (! array_key_exists($path, $available)) {
                $this->fail('paths', 'Choose only files listed in your repository preview.');
            }
            $total += $available[$path];
        }

        if ($total > self::MAX_BYTES) {
            $this->fail('paths', 'The selected files exceed 7 MB. Choose fewer files.');
        }

        $files = [];
        $bytes = 0;

        foreach (array_chunk($paths, 5) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk, $preview): array {
                return array_map(function (string $path) use ($pool, $preview): PromiseInterface {
                    $encodedPath = implode('/', array_map(rawurlencode(...), explode('/', $path)));

                    return $pool->as($path)->connectTimeout(5)->timeout(20)->withoutRedirecting()
                        ->withOptions(['progress' => function (float $downloadTotal, float $downloaded): void {
                            if (max($downloadTotal, $downloaded) > self::MAX_BYTES) {
                                $this->fail('paths', 'A selected file exceeds 7 MB. Choose fewer files.');
                            }
                        }])->get("https://raw.githubusercontent.com/{$preview['repository']}/{$preview['commit']}/{$encodedPath}");
                }, $chunk);
            }, concurrency: 5);

            foreach ($chunk as $path) {
                $response = $responses[$path];
                if ($response instanceof Throwable || ! $response->successful()) {
                    $this->fail('paths', "Could not download {$path}. Nothing was imported. Try again.");
                }
                $content = $response->body();
                $bytes += strlen($content);
                if ($bytes > self::MAX_BYTES || ! mb_check_encoding($content, 'UTF-8') || mb_strlen($content) > Lesson::MAX_CONTENT_LENGTH) {
                    $this->fail('paths', "{$path} is too large or is not UTF-8 Markdown. Choose fewer files.");
                }
                $files[] = compact('path', 'content');
            }
        }

        return $files;
    }

    /** @return array<string, mixed> */
    private function api(string $path): array
    {
        try {
            $response = Http::acceptJson()->withUserAgent('StudyHub')->connectTimeout(5)->timeout(20)
                ->withoutRedirecting()->get("https://api.github.com/{$path}");
        } catch (ConnectionException) {
            $this->fail('repository_url', 'Could not reach GitHub. Try again shortly.');
        }

        if (! $response->successful()) {
            $this->fail('repository_url', match ($response->status()) {
                403, 429 => 'GitHub is limiting requests. Try again later or import a local folder.',
                404 => 'Repository not found. Check the URL and make sure it is public.',
                default => 'GitHub is unavailable. Try again shortly.',
            });
        }

        return $response->json();
    }

    private function isMarkdown(string $path): bool
    {
        if (strlen($path) > 1000 || ! preg_match('/\.(md|markdown)$/i', $path)) {
            return false;
        }

        foreach (explode('/', $path) as $folder) {
            if (str_starts_with($folder, '.') || str_starts_with($folder, '_')
                || in_array($folder, ['node_modules', 'vendor', 'dist', 'build'])) {
                return false;
            }
        }

        return true;
    }

    private function key(User $user, string $token): string
    {
        return "github-import:{$user->id}:{$token}";
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
