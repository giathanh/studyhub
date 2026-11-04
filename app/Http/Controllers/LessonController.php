<?php

namespace App\Http\Controllers;

use App\Actions\Lessons\MoveLesson;
use App\Enums\StudyStatus;
use App\Http\Requests\LessonRequest;
use App\Http\Requests\LessonStatusRequest;
use App\Http\Requests\MoveLessonRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\LessonResource;
use App\Http\Resources\NoteResource;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function create(Course $course): Response
    {
        Gate::authorize('update', $course);

        return Inertia::render('Lessons/Create', [
            'course' => new CourseResource($course),
            'statuses' => StudyStatus::options(),
        ]);
    }

    public function store(LessonRequest $request, Course $course): RedirectResponse
    {
        $status = $request->enum('status', StudyStatus::class) ?? StudyStatus::NotStarted;

        $lesson = new Lesson($request->safe()->except('status'));
        $lesson->fill($lesson->statusAttributes($status));
        $lesson->position = ((int) $course->lessons()->max('position')) + 1;
        $course->lessons()->save($lesson);

        Inertia::flash('success', 'Lesson created.');

        return to_route('courses.lessons.show', [$course, $lesson]);
    }

    public function show(Course $course, Lesson $lesson): Response
    {
        Gate::authorize('view', $course);

        $notes = $lesson->notes()
            ->where('user_id', $course->user_id)
            ->latest('id')
            ->paginate(10);

        return Inertia::render('Lessons/Show', [
            'course' => new CourseResource($course),
            'lesson' => new LessonResource($lesson),
            'statuses' => StudyStatus::options(),
            'notes' => NoteResource::collection($notes),
        ]);
    }

    public function edit(Course $course, Lesson $lesson): Response
    {
        Gate::authorize('update', $course);

        return Inertia::render('Lessons/Edit', [
            'course' => new CourseResource($course),
            'lesson' => new LessonResource($lesson),
            'statuses' => StudyStatus::options(),
        ]);
    }

    public function update(LessonRequest $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $attributes = $request->safe()->except('status');

        if ($request->filled('status')) {
            $attributes = [
                ...$attributes,
                ...$lesson->statusAttributes($request->enum('status', StudyStatus::class)),
            ];
        }

        $lesson->update($attributes);

        Inertia::flash('success', 'Lesson updated.');

        return to_route('courses.lessons.show', [$course, $lesson]);
    }

    public function updateStatus(LessonStatusRequest $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $lesson->update($lesson->statusAttributes($request->enum('status', StudyStatus::class)));

        Inertia::flash('success', 'Lesson status updated.');

        return back();
    }

    public function move(MoveLessonRequest $request, Course $course, Lesson $lesson, MoveLesson $moveLesson): RedirectResponse
    {
        $moveLesson->handle($course, $lesson, $request->string('direction')->toString());

        return to_route('courses.show', [
            'course' => $course,
            ...($request->integer('page') > 1 ? ['page' => $request->integer('page')] : []),
        ]);
    }

    public function destroy(Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('update', $course);

        $lesson->delete();

        Inertia::flash('success', 'Lesson deleted.');

        return to_route('courses.show', $course);
    }
}
