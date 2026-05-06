<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'hasTeam'])
    ->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/tags.php';
require __DIR__ . '/teams.php';
require __DIR__ . '/tasks.php';
