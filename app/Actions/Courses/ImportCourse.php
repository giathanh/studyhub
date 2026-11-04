<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportCourse
{
    public function __construct(
        private ParseMarkdownCourse $parseMarkdownCourse,
        private LinkImportedLessons $linkImportedLessons,
    ) {}

    /**
     * Create a course and its lessons from markdown files, all or nothing.
     *
     * @param  list<array{path: string, content: ?string}>  $files
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $files, ?string $title = null): Course
    {
        $parsedCourse = $this->parseMarkdownCourse->handle($files);

        if ($parsedCourse['lessons'] === []) {
            throw ValidationException::withMessages([
                'files' => 'The selected files are empty.',
            ]);
        }

        return DB::transaction(function () use ($user, $parsedCourse, $title): Course {
            $course = $user->courses()->create([
                'title' => $title ?: $parsedCourse['title'] ?: 'Imported course',
                'description' => $parsedCourse['description'],
            ]);

            $position = 0;
            $lessonsByPath = [];

            foreach ($parsedCourse['lessons'] as $parsedLesson) {
                $lesson = $course->lessons()->create([
                    ...Arr::except($parsedLesson, 'source_path'),
                    'position' => ++$position,
                ]);

                if (isset($parsedLesson['source_path'])) {
                    $lessonsByPath[$parsedLesson['source_path']] = $lesson;
                }
            }

            $this->linkImportedLessons->handle($course, $lessonsByPath);

            return $course;
        });
    }
}
