<?php

namespace App\Http\Controllers;

use App\Actions\Prescriptions\BuildPrescriptionPdf;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ConsultationPrescriptionController extends Controller
{
    /**
     * Open the prescription of the consultation as a PDF. The doctor who wrote it
     * gets the official copy; the administrator gets a copy marked as not valid.
     */
    public function __invoke(Request $request, Consultation $consultation, BuildPrescriptionPdf $prescription): Response
    {
        Gate::authorize('downloadPrescription', $consultation);

        $consultation->load(['patient', 'doctor.user', 'doctor.specialty', 'prescriptions']);

        abort_if($consultation->prescriptions->isEmpty(), 404);

        $isOfficial = $request->user()->can('issuePrescription', $consultation);

        AuditLog::record(
            AuditAction::Exported,
            $consultation,
            $isOfficial ? 'Generó la receta médica en PDF' : 'Consultó la receta médica en PDF (copia sin validez)',
            $consultation->patient,
        );

        return $prescription->handle($consultation, $isOfficial)
            ->stream($prescription->filename($consultation));
    }
}
