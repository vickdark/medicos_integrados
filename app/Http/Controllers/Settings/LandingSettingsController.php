<?php

namespace App\Http\Controllers\Settings;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateLandingSettingsRequest;
use App\Models\AppSetting;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;

class LandingSettingsController extends Controller
{
    /**
     * Choose whether the landing page shows the doctors of the clinic.
     */
    public function update(UpdateLandingSettingsRequest $request): RedirectResponse
    {
        $show = $request->boolean('show_doctors');

        AppSetting::setShowDoctorsOnLanding($show);

        AuditLog::record(AuditAction::Updated, null, $show
            ? 'Activó la vista pública de los médicos en la página de inicio'
            : 'Desactivó la vista pública de los médicos en la página de inicio');

        return back()->with('success', $show
            ? 'Los médicos se muestran ahora en la página de inicio.'
            : 'Los médicos ya no se muestran en la página de inicio.');
    }
}
