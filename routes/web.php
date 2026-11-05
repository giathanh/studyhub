<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GithubCourseImportController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudySessionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/settings/profile', ProfileController::class)->name('settings.profile');
    Route::inertia('/settings/password', 'Settings/Password')->name('settings.password');

    Route::get('/courses/import', [CourseImportController::class, 'create'])->name('courses.import.create');
    Route::post('/courses/import', [CourseImportController::class, 'store'])->name('courses.import.store');

    Route::post('/courses/import/github/preview', [GithubCourseImportController::class, 'preview'])->middleware('throttle:10,1')->name('courses.import.github.preview');
    Route::post('/courses/import/github', [GithubCourseImportController::class, 'store'])->middleware('throttle:5,1')->name('courses.import.github.store');

    Route::resource('courses', CourseController::class);
    Route::resource('notes', NoteController::class);
    Route::resource('study-sessions', StudySessionController::class)->only(['index', 'store', 'destroy']);

    Route::scopeBindings()->group(function (): void {
        Route::resource('courses.lessons', LessonController::class)->except('index');
        Route::patch('courses/{course}/lessons/{lesson}/status', [LessonController::class, 'updateStatus'])
            ->name('courses.lessons.status');
        Route::patch('courses/{course}/lessons/{lesson}/move', [LessonController::class, 'move'])
            ->name('courses.lessons.move');
    });
});
