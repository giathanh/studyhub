<?php

namespace App\Actions\Lessons;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;

class MoveLesson
{
    /**
     * Move a lesson one step up or down within its course and renumber positions.
     */
    public function handle(Course $course, Lesson $lesson, string $direction): void
    {
        DB::transaction(function () use ($course, $lesson, $direction): void {
            $lessons = $course->lessons()
                ->orderBy('position')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'position'])
                ->values();

            $currentIndex = $lessons->search(fn (Lesson $item): bool => $item->id === $lesson->id);

            if ($currentIndex === false) {
                return;
            }

            $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

            if (! $lessons->has($targetIndex)) {
                return;
            }

            $currentLesson = $lessons->get($currentIndex);
            $lessons->put($currentIndex, $lessons->get($targetIndex));
            $lessons->put($targetIndex, $currentLesson);

            foreach ($lessons as $index => $orderedLesson) {
                $position = $index + 1;

                if ($orderedLesson->position !== $position) {
                    Lesson::whereKey($orderedLesson->id)->update(['position' => $position]);
                }
            }
        });
    }
}
