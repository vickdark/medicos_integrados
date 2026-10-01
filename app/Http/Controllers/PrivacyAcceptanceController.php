<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptPrivacyPolicyRequest;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyAcceptanceController extends Controller
{
    /**
     * Ask the patient to accept the current version of the personal data policy.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->mustAcceptPrivacyPolicy()) {
            return to_route('dashboard');
        }

        return Inertia::render('PrivacyAccept', [
            'version' => AppSetting::privacyVersion(),
            'updatedAt' => AppSetting::privacyPolicy()['updated_at'],
            'previousVersion' => $request->user()->privacy_policy_version,
        ]);
    }

    /**
     * Record the acceptance with its date and the version accepted.
     */
    public function store(AcceptPrivacyPolicyRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'privacy_accepted_at' => now(),
            'privacy_policy_version' => AppSetting::privacyVersion(),
        ])->save();

        return redirect()->intended(route('dashboard'))->with('success', 'Gracias. Registramos tu autorización.');
    }
}
