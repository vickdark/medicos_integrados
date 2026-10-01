<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Users\SaveDoctorSignature;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDoctorSignatureRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets doctors upload or remove the image of their own signature.
 */
class DoctorSignatureController extends Controller
{
    /**
     * Save the signature uploaded by the doctor.
     */
    public function update(UpdateDoctorSignatureRequest $request, SaveDoctorSignature $saveSignature): RedirectResponse
    {
        $saveSignature->handle($request->user()->doctor, $request->file('signature'));

        AuditLog::record(AuditAction::Updated, null, 'Registró su firma para los documentos clínicos');

        return back()->with('success', 'Firma guardada. Se imprimirá en tus recetas y documentos.');
    }

    /**
     * Remove the doctor's signature.
     */
    public function destroy(Request $request, SaveDoctorSignature $saveSignature): RedirectResponse
    {
        $doctor = $request->user()->doctor;

        abort_if($doctor === null, 403);

        $saveSignature->handle($doctor, null, remove: true);

        AuditLog::record(AuditAction::Deleted, null, 'Eliminó su firma de los documentos clínicos');

        return back()->with('success', 'Firma eliminada. Tus documentos se marcarán como no válidos hasta que registres una nueva.');
    }
}
