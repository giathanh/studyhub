<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseImportController;
use App\Http\Controllers\LessonController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::middleware('auth')->group(function (): void {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('/settings/password', 'Settings/Password')->name('settings.password');

    Route::get('/courses/import', [CourseImportController::class, 'create'])->name('courses.import.create');
    Route::post('/courses/import', [CourseImportController::class, 'store'])->name('courses.import.store');

    Route::resource('courses', CourseController::class);

    Route::scopeBindings()->group(function (): void {
        Route::resource('courses.lessons', LessonController::class)->except('index');
    });
});
