<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptPrivacyPolicyRequest;
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
            'version' => config('privacy.version'),
            'updatedAt' => config('privacy.updated_at'),
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
            'privacy_policy_version' => config('privacy.version'),
        ])->save();

        return redirect()->intended(route('dashboard'))->with('success', 'Gracias. Registramos tu autorización.');
    }
}
