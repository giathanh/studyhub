<?php

namespace App\Actions\Courses;

use Illuminate\Support\Str;

class ParseMarkdownCourse
{
    private const int MAX_TITLE_LENGTH = 255;

    private const int MAX_DESCRIPTION_LENGTH = 5000;

    /**
     * Turn markdown files into a course outline.
     *
     * Several files become one lesson each, grouped by their top-level folder.
     * A single file is one document: its H1 names the course, the text before
     * the first H2 describes it, and each H2 becomes a lesson.
     *
     * @param  list<array{path: string, content: ?string}>  $files
     * @return array{title: ?string, description: ?string, lessons: list<array{section: ?string, title: string, content: ?string}>}
     */
    public function handle(array $files): array
    {
        $documents = collect($files)
            ->map(fn (array $file): array => [
                'path' => $file['path'],
                'content' => $this->normalize($file['content'] ?? ''),
            ])
            ->reject(fn (array $document): bool => trim($document['content']) === '')
            ->sort(fn (array $first, array $second): int => strnatcasecmp($first['path'], $second['path']))
            ->values();

        if ($documents->count() === 1) {
            return $this->parseSingleDocument($documents->first());
        }

        return [
            'title' => null,
            'description' => null,
            'lessons' => $documents->map($this->parseLessonFile(...))->all(),
        ];
    }

    /**
     * @param  array{path: string, content: string}  $document
     * @return array{title: ?string, description: ?string, lessons: list<array{section: ?string, title: string, content: ?string}>}
     */
    private function parseSingleDocument(array $document): array
    {
        [$title, $body] = $this->extractTitle($document['content']);
        $title ??= $this->titleFromPath($document['path']);

        $chunks = $this->splitAtSecondLevelHeadings($body);

        if ($chunks['sections'] === []) {
            return [
                'title' => $title,
                'description' => null,
                'lessons' => [['section' => null, 'title' => $title, 'content' => $this->nullIfBlank($body)]],
            ];
        }

        return [
            'title' => $title,
            'description' => $this->nullIfBlank(mb_substr(trim($chunks['intro']), 0, self::MAX_DESCRIPTION_LENGTH)),
            'lessons' => array_map(
                fn (array $section): array => ['section' => null, ...$section],
                $chunks['sections'],
            ),
        ];
    }

    /**
     * @param  array{path: string, content: string}  $document
     * @return array{section: ?string, title: string, content: ?string}
     */
    private function parseLessonFile(array $document): array
    {
        [$title, $body] = $this->extractTitle($document['content']);

        return [
            'section' => $this->sectionFromPath($document['path']),
            'title' => $title ?? $this->titleFromPath($document['path']),
            'content' => $this->nullIfBlank($body),
        ];
    }

    /**
     * Pull out the first H1 and return it with the remaining text.
     *
     * @return array{0: ?string, 1: string}
     */
    private function extractTitle(string $markdown): array
    {
        $lines = explode("\n", $markdown);

        foreach ($this->headingsOutsideCodeFences($lines) as $lineNumber => $heading) {
            if ($heading['level'] === 1) {
                unset($lines[$lineNumber]);

                return [$heading['text'], implode("\n", $lines)];
            }
        }

        return [null, $markdown];
    }

    /**
     * @return array{intro: string, sections: list<array{title: string, content: ?string}>}
     */
    private function splitAtSecondLevelHeadings(string $markdown): array
    {
        $lines = explode("\n", $markdown);

        $headings = array_filter(
            $this->headingsOutsideCodeFences($lines),
            fn (array $heading): bool => $heading['level'] === 2,
        );

        $starts = array_keys($headings);
        $sections = [];

        foreach ($starts as $index => $start) {
            $end = $starts[$index + 1] ?? count($lines);

            $sections[] = [
                'title' => $headings[$start]['text'],
                'content' => $this->nullIfBlank(implode("\n", array_slice($lines, $start + 1, $end - $start - 1))),
            ];
        }

        return [
            'intro' => implode("\n", array_slice($lines, 0, $starts[0] ?? count($lines))),
            'sections' => $sections,
        ];
    }

    /**
     * Find ATX headings, skipping code fences: a `# comment` in a shell
     * snippet is not a heading.
     *
     * @param  list<string>  $lines
     * @return array<int, array{level: int, text: string}>
     */
    private function headingsOutsideCodeFences(array $lines): array
    {
        $headings = [];
        $openFence = null;

        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/^\s*(`{3,}|~{3,})(.*)$/', $line, $fence) === 1) {
                if ($openFence === null) {
                    $openFence = $fence[1];

                    continue;
                }

                if ($fence[1][0] === $openFence[0]
                    && strlen($fence[1]) >= strlen($openFence)
                    && trim($fence[2]) === '') {
                    $openFence = null;
                }

                continue;
            }

            if ($openFence === null && preg_match('/^(#{1,2})\s+(.+?)\s*#*\s*$/', $line, $heading) === 1) {
                $headings[$lineNumber] = [
                    'level' => strlen($heading[1]),
                    'text' => mb_substr($heading[2], 0, self::MAX_TITLE_LENGTH),
                ];
            }
        }

        return $headings;
    }

    private function sectionFromPath(string $path): ?string
    {
        $segments = explode('/', $path);

        return count($segments) > 1 ? $this->humanize($segments[0]) : null;
    }

    private function titleFromPath(string $path): string
    {
        return $this->humanize(pathinfo($path, PATHINFO_FILENAME));
    }

    /**
     * Turn `02-identity-and-access` into `Identity And Access`.
     */
    private function humanize(string $name): string
    {
        $headline = Str::headline(preg_replace('/^\d+[-_.\s]*/', '', $name) ?: $name);

        return mb_substr($headline, 0, self::MAX_TITLE_LENGTH);
    }

    private function normalize(string $markdown): string
    {
        return str_replace(["\r\n", "\r"], "\n", preg_replace('/^\x{FEFF}/u', '', $markdown) ?? $markdown);
    }

    private function nullIfBlank(string $text): ?string
    {
        return trim($text) === '' ? null : trim($text, "\n");
    }
}
