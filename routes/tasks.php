<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::controller(TaskController::class)
    ->middleware(['auth', 'verified', 'hasTeam'])
    ->group(function () {
        Route::get('tasks', 'index')
            ->name('tasks.index');
        Route::post('tasks', 'store')
            ->name('tasks.store');
        Route::put('tasks/{task}', 'update')
            ->name('tasks.update');
        Route::delete('tasks/{task}', 'destroy')
            ->name('tasks.destroy');
    });
