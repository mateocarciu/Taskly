<?php

use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('teams/pending', [TeamController::class, 'pending'])
        ->name('teams.pending');
});
