<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\Lesson;

class LinkImportedLessons
{
    /**
     * Matches the target of a markdown link to a markdown file. Inline code
     * spans are consumed first so that links shown as code stay as written.
     */
    private const string LINK_PATTERN = '/`[^`]*`(*SKIP)(*FAIL)|\]\((?<path>[^)\s#?]+\.(?:md|markdown))(?<fragment>#[^)\s]*)?(?:\s[^)]*)?\)/iu';

    /**
     * Point links between imported files at the lessons made from them.
     *
     * Links to files outside the import are left unchanged.
     *
     * @param  array<string, Lesson>  $lessonsByPath
     */
    public function handle(Course $course, array $lessonsByPath): void
    {
        $lessonUrls = array_map(
            fn (Lesson $lesson): string => route('courses.lessons.show', [$course, $lesson], absolute: false),
            $lessonsByPath,
        );

        foreach ($lessonsByPath as $path => $lesson) {
            if ($lesson->content !== null) {
                $lesson->update(['content' => $this->rewriteLinks($lesson->content, $path, $lessonUrls)]);
            }
        }
    }

    /**
     * @param  array<string, string>  $lessonUrls
     */
    private function rewriteLinks(string $content, string $path, array $lessonUrls): string
    {
        $lines = explode("\n", $content);
        $openFence = null;

        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/^\s*(`{3,}|~{3,})(.*)$/', $line, $fence) === 1) {
                if ($openFence === null) {
                    $openFence = $fence[1];
                } elseif ($fence[1][0] === $openFence[0]
                    && strlen($fence[1]) >= strlen($openFence)
                    && trim($fence[2]) === '') {
                    $openFence = null;
                }

                continue;
            }

            if ($openFence === null) {
                $lines[$lineNumber] = preg_replace_callback(
                    self::LINK_PATTERN,
                    function (array $link) use ($path, $lessonUrls): string {
                        $url = $lessonUrls[$this->resolvePath($path, $link['path'])] ?? null;

                        return $url === null ? $link[0] : "]({$url}".($link['fragment'] ?? '').')';
                    },
                    $line,
                ) ?? $line;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Resolve a link against the folder of the file that contains it.
     */
    private function resolvePath(string $path, string $link): string
    {
        $segments = array_slice(explode('/', $path), 0, -1);

        foreach (explode('/', rawurldecode($link)) as $segment) {
            if ($segment === '..') {
                array_pop($segments);
            } elseif ($segment !== '.' && $segment !== '') {
                $segments[] = $segment;
            }
        }

        return implode('/', $segments);
    }
}
