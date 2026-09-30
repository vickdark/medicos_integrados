<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\ClinicalHistoryRequest;
use App\Models\AuditLog;
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
    public function __invoke(ClinicalHistoryRequest $request, Patient $patient): Response
    {
        $from = $request->periodStart();
        $to = $request->periodEnd();

        $consultations = $patient->consultations()
            ->with(['doctor.user', 'doctor.specialty', 'prescriptions', 'primaryDiagnosis', 'relatedDiagnoses'])
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

        AuditLog::record(AuditAction::Exported, $patient, "Abrió la historia clínica en PDF ({$period})");

        return Pdf::loadView('pdf.clinical-history', [
            'patient' => $patient,
            'consultations' => $consultations,
            'period' => $period,
            'includeNotes' => $request->user()->isStaff(),
            'generatedAt' => now(),
            'generatedBy' => $request->user()->name,
        ])
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4')
            ->stream('historia-clinica-'.Str::slug($patient->full_name).'-'.now()->format('Ymd').'.pdf');
    }
}
