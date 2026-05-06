<?php

use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'hasTeam'])
    ->group(function () {
        Route::controller(TagController::class)->group(function () {
            Route::get('tags', 'index')->name('tags.index');
            Route::post('tags', 'store')->name('tags.store');
            Route::put('tags/{tag}', 'update')->name('tags.update');
            Route::delete('tags/{tag}', 'destroy')->name('tags.destroy');
        });
    });
