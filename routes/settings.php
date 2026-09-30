<?php

use App\Http\Controllers\Settings\BrandingController;
use App\Http\Controllers\Settings\DoctorProfileController;
use App\Http\Controllers\Settings\LandingSettingsController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/doctor-profile', [DoctorProfileController::class, 'show'])->name('doctor-profile.show');

    Route::get('settings/branding', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::put('settings/branding', [BrandingController::class, 'update'])->name('branding.update');
    Route::delete('settings/branding', [BrandingController::class, 'destroy'])->name('branding.destroy');
    Route::put('settings/landing', [LandingSettingsController::class, 'update'])->name('landing-settings.update');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});
