<?php

namespace App\Http\Controllers;

use App\Enums\StudyStatus;
use App\Http\Resources\CourseResource;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $weekStart = today()->startOfWeek();
        $courses = $user->courses()->where('status', StudyStatus::InProgress)
            ->withLessonCounts()->latest('updated_at')->orderByDesc('id')
            ->paginate(6, ['*'], 'courses_page')->withQueryString();
        $lessons = Lesson::query()->whereHas('course', fn ($query) => $query->where('user_id', $user->id))
            ->where('status', '!=', StudyStatus::Completed)
            ->with('course:id,title')->orderBy('course_id')->orderBy('position')->orderBy('id')
            ->paginate(8, ['id', 'course_id', 'title'], 'lessons_page')->withQueryString();
        $notes = $user->notes()->latest('updated_at')->orderByDesc('id')
            ->paginate(5, ['id', 'title'], 'notes_page')->withQueryString();

        return Inertia::render('Dashboard', [
            'courses' => CourseResource::collection($courses),
            'lessons' => JsonResource::collection($lessons->through(fn (Lesson $lesson): array => [
                'id' => $lesson->id, 'course_id' => $lesson->course_id,
                'title' => $lesson->title, 'course_title' => $lesson->course->title,
            ])),
            'notes' => JsonResource::collection($notes->through(fn ($note): array => $note->only('id', 'title'))),
            'week' => [
                'start' => $weekStart->toDateString(),
                'end' => $weekStart->copy()->addDays(6)->toDateString(),
                'minutes' => (int) $user->studySessions()->whereBetween('studied_on', [
                    $weekStart->toDateString(), $weekStart->copy()->addDays(6)->toDateString(),
                ])->sum('minutes'),
            ],
        ]);
    }
}
