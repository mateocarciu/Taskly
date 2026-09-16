<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'hasTeam'])->group(function () {
    Route::post('notifications/read', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
});
