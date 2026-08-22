<?php

use App\Http\Controllers\Settings\ColumnSettingsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\Settings\UserManagementController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');

    // Team management
    Route::get('settings/teams', [TeamController::class, 'index'])
        ->name('teams.index');
    Route::post('settings/teams', [TeamController::class, 'store'])
        ->name('teams.store');
    Route::patch('settings/teams/{team}', [TeamController::class, 'update'])
        ->name('teams.update');
    Route::delete('settings/teams/{team}', [TeamController::class, 'destroy'])
        ->name('teams.destroy');
    Route::post('settings/teams/{team}/switch', [TeamController::class, 'switch'])
        ->name('teams.switch');

    // Team member management (owner/admin only)
    Route::post('settings/teams/{team}/members', [TeamController::class, 'addMember'])
        ->name('teams.members.store');
    Route::delete('settings/teams/{team}/members/{user}', [TeamController::class, 'removeMember'])
        ->name('teams.members.destroy');
    Route::patch('settings/teams/{team}/members/{user}/promote', [TeamController::class, 'promote'])
        ->name('teams.members.promote');
    Route::patch('settings/teams/{team}/members/{user}/demote', [TeamController::class, 'demote'])
        ->name('teams.members.demote');

    // Other settings
    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    Route::get('settings/columns', [ColumnSettingsController::class, 'index'])
        ->name('settings.columns.index');

    // User management (owner/admin only)
    Route::get('settings/users', [UserManagementController::class, 'index'])
        ->name('settings.users.index');
    Route::post('settings/users', [UserManagementController::class, 'store'])
        ->name('settings.users.store');
    Route::delete('settings/users/{user}', [UserManagementController::class, 'destroy'])
        ->name('settings.users.destroy');
    Route::post('settings/users/transfer-ownership', [UserManagementController::class, 'transferOwnership'])
        ->name('settings.users.transfer-ownership');
});
