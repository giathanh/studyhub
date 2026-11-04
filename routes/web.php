<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseImportController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::middleware('auth')->group(function (): void {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('/settings/password', 'Settings/Password')->name('settings.password');

    Route::get('/courses/import', [CourseImportController::class, 'create'])->name('courses.import.create');
    Route::post('/courses/import', [CourseImportController::class, 'store'])->name('courses.import.store');

    Route::resource('courses', CourseController::class);
    Route::resource('notes', NoteController::class);

    Route::scopeBindings()->group(function (): void {
        Route::resource('courses.lessons', LessonController::class)->except('index');
        Route::patch('courses/{course}/lessons/{lesson}/status', [LessonController::class, 'updateStatus'])
            ->name('courses.lessons.status');
        Route::patch('courses/{course}/lessons/{lesson}/move', [LessonController::class, 'move'])
            ->name('courses.lessons.move');
    });
});
