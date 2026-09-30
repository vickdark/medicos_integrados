<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Branding\BrandPalette;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBrandingRequest;
use App\Models\AppSetting;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    /**
     * Show the appearance settings that apply to the whole application.
     */
    public function edit(): Response
    {
        Gate::authorize('manage-branding');

        return Inertia::render('settings/Branding', [
            'color' => AppSetting::brandColor(),
            'defaultColor' => BrandPalette::DEFAULT_COLOR,
            'presets' => collect(BrandPalette::PRESETS)
                ->map(fn (string $color, string $name): array => ['name' => $name, 'color' => $color])
                ->values(),
        ]);
    }

    /**
     * Save the accent color of the whole application.
     */
    public function update(UpdateBrandingRequest $request): RedirectResponse
    {
        AppSetting::setBrandColor($request->validated('color'));

        AuditLog::record(AuditAction::Updated, null, 'Cambió el color de la aplicación a '.BrandPalette::normalize($request->validated('color')));

        return back()->with('success', 'Color actualizado para toda la aplicación.');
    }

    /**
     * Go back to the default accent color.
     */
    public function destroy(): RedirectResponse
    {
        Gate::authorize('manage-branding');

        AppSetting::resetBrandColor();

        AuditLog::record(AuditAction::Updated, null, 'Restableció el color de la aplicación');

        return back()->with('success', 'Se restableció el color original.');
    }
}
