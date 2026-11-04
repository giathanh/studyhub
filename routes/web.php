<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::middleware('auth')->group(function (): void {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('/settings/password', 'Settings/Password')->name('settings.password');
});
