<?php

use App\Http\Controllers\Settings\BrandingController;
use App\Http\Controllers\Settings\DoctorProfileController;
use App\Http\Controllers\Settings\DoctorSignatureController;
use App\Http\Controllers\Settings\LandingContentController;
use App\Http\Controllers\Settings\LandingSettingsController;
use App\Http\Controllers\Settings\PersonalDataController;
use App\Http\Controllers\Settings\PrivacySettingsController;
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
    Route::post('settings/doctor-profile/signature', [DoctorSignatureController::class, 'update'])->name('doctor-profile.signature.update');
    Route::delete('settings/doctor-profile/signature', [DoctorSignatureController::class, 'destroy'])->name('doctor-profile.signature.destroy');

    Route::get('settings/branding', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::put('settings/branding', [BrandingController::class, 'update'])->name('branding.update');
    Route::delete('settings/branding', [BrandingController::class, 'destroy'])->name('branding.destroy');
    Route::get('settings/privacy', [PrivacySettingsController::class, 'edit'])->name('privacy-settings.edit');
    Route::put('settings/privacy', [PrivacySettingsController::class, 'update'])->name('privacy-settings.update');
    Route::post('settings/privacy/publish', [PrivacySettingsController::class, 'publish'])->name('privacy-settings.publish');
    Route::get('settings/landing-content', [LandingContentController::class, 'edit'])->name('landing-content.edit');
    Route::put('settings/landing-content', [LandingContentController::class, 'update'])->name('landing-content.update');
    Route::delete('settings/landing-content', [LandingContentController::class, 'destroy'])->name('landing-content.destroy');
    Route::put('settings/landing', [LandingSettingsController::class, 'update'])->name('landing-settings.update');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::get('settings/personal-data', [PersonalDataController::class, 'edit'])->name('personal-data.edit');
    Route::post('settings/personal-data/requests', [PersonalDataController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('personal-data.requests.store');
    Route::get('settings/personal-data/download', [PersonalDataController::class, 'download'])
        ->middleware('password.confirm')
        ->name('personal-data.download');

    Route::inertia('settings/notifications', 'settings/Notifications')->name('notifications.edit');
});
