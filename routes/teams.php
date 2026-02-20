<?php

use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('teams/select', [TeamController::class, 'select'])
        ->name('teams.select');
    Route::post('teams/join', [TeamController::class, 'join'])
        ->name('teams.join');
});
