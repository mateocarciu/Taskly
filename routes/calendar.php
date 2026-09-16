<?php

use App\Http\Controllers\CalendarController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'hasTeam'])->group(function () {
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('calendar/events', [CalendarController::class, 'store'])->name('calendar.events.store');
    Route::get('calendar/events/{event}', [CalendarController::class, 'show'])->name('calendar.events.show');
    Route::put('calendar/events/{event}', [CalendarController::class, 'update'])->name('calendar.events.update');
    Route::delete('calendar/events/{event}', [CalendarController::class, 'destroy'])->name('calendar.events.destroy');
    Route::post('calendar/events/{event}/respond/{response}', [CalendarController::class, 'respond'])->name('calendar.events.respond');
    Route::get('calendar/events/{event}/room', [CalendarController::class, 'room'])->name('calendar.events.room');
});
