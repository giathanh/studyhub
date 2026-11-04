<?php

namespace App\Http\Controllers;

use App\Actions\Courses\ImportCourse;
use App\Actions\Courses\ReadGithubCourse;
use App\Http\Requests\GithubCourseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class GithubCourseImportController extends Controller
{
    public function preview(GithubCourseRequest $request, ReadGithubCourse $github): JsonResponse
    {
        return response()->json($github->preview($request->user(), $request->validated('repository_url')));
    }

    public function store(GithubCourseRequest $request, ReadGithubCourse $github, ImportCourse $import): RedirectResponse
    {
        $files = $github->files($request->user(), $request->validated('token'), $request->validated('paths'));
        $course = $import->handle($request->user(), $files, $request->validated('title'));
        Inertia::flash('success', 'Course imported from GitHub.');

        return to_route('courses.show', $course);
    }
}
