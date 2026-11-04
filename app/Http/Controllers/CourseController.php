<?php

namespace App\Http\Controllers;

use App\Enums\StudyStatus;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\LessonResource;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = $request->user()->courses()
            ->withLessonCounts()
            ->latest('id')
            ->paginate(12);

        return Inertia::render('Courses/Index', [
            'courses' => CourseResource::collection($courses),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Courses/Create', [
            'statuses' => StudyStatus::options(),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $course = $request->user()->courses()->create($request->validated());

        Inertia::flash('success', 'Course created.');

        return to_route('courses.show', $course);
    }

    public function show(Course $course): Response
    {
        Gate::authorize('view', $course);

        $lessons = $course->lessons()
            ->select(['id', 'course_id', 'section', 'title', 'status', 'position', 'completed_at'])
            ->orderBy('position')
            ->orderBy('id')
            ->paginate(50);

        return Inertia::render('Courses/Show', [
            'course' => new CourseResource($course->loadLessonCounts()),
            'lessons' => LessonResource::collection($lessons),
            'statuses' => StudyStatus::options(),
        ]);
    }

    public function edit(Course $course): Response
    {
        Gate::authorize('update', $course);

        return Inertia::render('Courses/Edit', [
            'course' => new CourseResource($course),
            'statuses' => StudyStatus::options(),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        Inertia::flash('success', 'Course updated.');

        return to_route('courses.show', $course);
    }

    /**
     * Lessons are removed with the course; notes stay and lose the link.
     */
    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('delete', $course);

        $course->delete();

        Inertia::flash('success', 'Course deleted.');

        return to_route('courses.index');
    }
}
