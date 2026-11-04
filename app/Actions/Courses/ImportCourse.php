<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportCourse
{
    public function __construct(private ParseMarkdownCourse $parseMarkdownCourse) {}

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

            foreach ($parsedCourse['lessons'] as $lesson) {
                $course->lessons()->create([...$lesson, 'position' => ++$position]);
            }

            return $course;
        });
    }
}
