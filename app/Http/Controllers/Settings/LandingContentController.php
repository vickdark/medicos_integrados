<?php

namespace App\Http\Controllers\Settings;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateLandingContentRequest;
use App\Models\AppSetting;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LandingContentController extends Controller
{
    /**
     * Show the texts of the public landing page for the administrator to edit.
     */
    public function edit(): Response
    {
        Gate::authorize('manage-branding');

        return Inertia::render('settings/Landing', [
            'content' => AppSetting::landingContent(),
        ]);
    }

    /**
     * Save the contact data and the texts of the landing page.
     */
    public function update(UpdateLandingContentRequest $request): RedirectResponse
    {
        $content = $request->validated();

        foreach (['phone', 'email', 'address'] as $field) {
            $content['contact'][$field] = $content['contact'][$field] ?? '';
        }

        AppSetting::setLandingContent($content);

        AuditLog::record(AuditAction::Updated, null, 'Actualizó los textos y datos de contacto de la página de inicio');

        return back()->with('success', 'Página de inicio actualizada.');
    }

    /**
     * Go back to the original texts.
     */
    public function destroy(): RedirectResponse
    {
        Gate::authorize('manage-branding');

        AppSetting::resetLandingContent();

        AuditLog::record(AuditAction::Updated, null, 'Restableció los textos originales de la página de inicio');

        return back()->with('success', 'Se restablecieron los textos originales.');
    }
}
