<?php

namespace App\Http\Controllers\Settings;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePrivacySettingsRequest;
use App\Models\AppSetting;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PrivacySettingsController extends Controller
{
    /**
     * Show the data of the controller and the version of the personal data policy.
     */
    public function edit(): Response
    {
        Gate::authorize('manage-branding');

        $policy = AppSetting::privacyPolicy();

        return Inertia::render('settings/Privacy', [
            'policy' => $policy,
            'defaultCompany' => config('privacy.company'),
        ]);
    }

    /**
     * Save the data of the controller shown in the policy.
     */
    public function update(UpdatePrivacySettingsRequest $request): RedirectResponse
    {
        AppSetting::savePrivacyController($request->validated());

        AuditLog::record(AuditAction::Updated, null, 'Actualizó los datos del responsable de la política de datos personales');

        return back()->with('success', 'Datos del responsable actualizados.');
    }

    /**
     * Publish a new version of the policy: every patient has to accept it again.
     */
    public function publish(): RedirectResponse
    {
        Gate::authorize('manage-branding');

        $version = AppSetting::publishPrivacyVersion();

        AuditLog::record(AuditAction::Updated, null, "Publicó la versión {$version} de la política de datos personales");

        return back()->with('success', "Versión {$version} publicada. Los pacientes deberán aceptarla de nuevo.");
    }
}
