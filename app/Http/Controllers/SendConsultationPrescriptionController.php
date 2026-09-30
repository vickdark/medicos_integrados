<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\SendPrescriptionRequest;
use App\Mail\PrescriptionMail;
use App\Models\AuditLog;
use App\Models\Consultation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendConsultationPrescriptionController extends Controller
{
    /**
     * Email the prescription PDF to the address the doctor confirms: the patient's
     * registered email or the one they gave during the consultation.
     */
    public function __invoke(SendPrescriptionRequest $request, Consultation $consultation): RedirectResponse
    {
        $consultation->loadMissing(['patient', 'prescriptions']);

        abort_if($consultation->prescriptions->isEmpty(), 404);

        $email = $request->validated('email');

        try {
            Mail::to($email)->send(new PrescriptionMail($consultation));
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'No se pudo enviar la receta por correo. Inténtalo de nuevo.');
        }

        AuditLog::record(AuditAction::Exported, $consultation, "Envió la receta médica por correo a {$email}", $consultation->patient);

        return back()->with('success', "Receta enviada a {$email}.");
    }
}
