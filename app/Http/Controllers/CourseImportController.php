<?php

namespace App\Http\Controllers;

use App\Actions\Courses\ImportCourse;
use App\Http\Requests\ImportCourseRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseImportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Courses/Import');
    }

    public function store(ImportCourseRequest $request, ImportCourse $importCourse): RedirectResponse
    {
        $course = $importCourse->handle(
            $request->user(),
            $request->validated('files'),
            $request->validated('title'),
        );

        Inertia::flash('success', 'Course imported.');

        return to_route('courses.show', $course);
    }
}
