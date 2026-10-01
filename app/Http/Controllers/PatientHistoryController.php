<?php

namespace App\Http\Controllers;

use App\Actions\Verification\DocumentVerifier;
use App\Enums\AuditAction;
use App\Http\Requests\ClinicalHistoryRequest;
use App\Models\AuditLog;
use App\Models\ClinicalHistoryExport;
use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PatientHistoryController extends Controller
{
    /**
     * Open the patient's clinical history as a PDF in the browser. Without dates it includes
     * the whole history; with dates, only the consultations inside the period.
     */
    public function __invoke(ClinicalHistoryRequest $request, Patient $patient, DocumentVerifier $verifier): Response
    {
        $from = $request->periodStart();
        $to = $request->periodEnd();

        $consultations = $patient->consultations()
            ->with(['doctor.user', 'doctor.specialty', 'prescriptions', 'primaryDiagnosis', 'relatedDiagnoses', 'addenda'])
            ->when($from, fn ($query) => $query->where('consulted_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('consulted_at', '<=', $to))
            ->orderBy('consulted_at')
            ->get();

        $period = match (true) {
            $from && $to => 'Del '.$from->format('d/m/Y').' al '.$to->format('d/m/Y'),
            (bool) $from => 'Desde el '.$from->format('d/m/Y'),
            (bool) $to => 'Hasta el '.$to->format('d/m/Y'),
            default => 'Historial completo',
        };

        $user = $request->user();
        $includeNotes = $user->isStaff();

        $export = ClinicalHistoryExport::query()->create([
            'patient_id' => $patient->id,
            'issued_by' => $user->id,
            'issued_by_name' => $user->name,
            'issued_by_role' => $user->role->label(),
            'period_from' => $from,
            'period_to' => $to,
            'period_label' => $period,
            'consultation_ids' => $consultations->modelKeys(),
            'includes_notes' => $includeNotes,
        ]);

        AuditLog::record(AuditAction::Exported, $patient, "Abrió la historia clínica en PDF {$export->number} ({$period})");

        return Pdf::loadView('pdf.clinical-history', [
            'patient' => $patient,
            'consultations' => $consultations,
            'period' => $period,
            'number' => $export->number,
            'verification' => $verifier->stamp($export->verification_code),
            'includeNotes' => $includeNotes,
            'generatedAt' => now(),
            'generatedBy' => $user->name,
        ])
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4')
            ->stream('historia-clinica-'.Str::slug($patient->full_name).'-'.now()->format('Ymd').'.pdf');
    }
}
