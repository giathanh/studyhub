<?php

namespace App\Http\Controllers;

use App\Http\Requests\LessonRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\LessonResource;
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
        ]);
    }

    public function store(LessonRequest $request, Course $course): RedirectResponse
    {
        $lesson = $course->lessons()->create([
            ...$request->validated(),
            'position' => $course->lessons()->max('position') + 1,
        ]);

        Inertia::flash('success', 'Lesson created.');

        return to_route('courses.lessons.show', [$course, $lesson]);
    }

    public function show(Course $course, Lesson $lesson): Response
    {
        Gate::authorize('view', $course);

        return Inertia::render('Lessons/Show', [
            'course' => new CourseResource($course),
            'lesson' => new LessonResource($lesson),
        ]);
    }

    public function edit(Course $course, Lesson $lesson): Response
    {
        Gate::authorize('update', $course);

        return Inertia::render('Lessons/Edit', [
            'course' => new CourseResource($course),
            'lesson' => new LessonResource($lesson),
        ]);
    }

    public function update(LessonRequest $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $lesson->update($request->validated());

        Inertia::flash('success', 'Lesson updated.');

        return to_route('courses.lessons.show', [$course, $lesson]);
    }

    public function destroy(Course $course, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('update', $course);

        $lesson->delete();

        Inertia::flash('success', 'Lesson deleted.');

        return to_route('courses.show', $course);
    }
}
