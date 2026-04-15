<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\TaskSequenceController;
use App\Http\Controllers\Api\ColumnTaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'hasTeam'])
    ->group(function () {
        Route::controller(TaskController::class)->group(function () {
            Route::get('tasks', 'index')->name('tasks.index');
            Route::get('tasks/{task}', 'show')->name('tasks.show');
            Route::post('tasks', 'store')->name('tasks.store');
            Route::put('tasks/{task}', 'update')->name('tasks.update');
            Route::post('tasks/{task}/comments', 'storeComment')->name('tasks.comments.store');
            Route::delete('tasks/{task}', 'destroy')->name('tasks.destroy');
        });

        Route::controller(ColumnController::class)->group(function () {
            Route::post('columns', 'store')->name('columns.store');
            Route::put('columns/{column}', 'update')->name('columns.update');
            Route::delete('columns/{column}', 'destroy')->name('columns.destroy');
        });

        Route::put('tasks/{task}/sequence', [TaskSequenceController::class, 'update'])->name('tasks.sequence.update');
        Route::get('columns/{column}/tasks', [ColumnTaskController::class, 'index'])->name('columns.tasks.index');
    });
