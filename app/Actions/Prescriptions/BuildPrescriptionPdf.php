<?php

namespace App\Actions\Prescriptions;

use App\Models\Consultation;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Str;

/**
 * Renders the prescription of a consultation. Only the copy issued by the
 * prescribing doctor is official; any other copy carries a notice saying so.
 */
class BuildPrescriptionPdf
{
    /**
     * Build the PDF of the consultation's prescription.
     */
    public function handle(Consultation $consultation, bool $isOfficial): PDF
    {
        $consultation->loadMissing(['patient', 'doctor.user', 'doctor.specialty', 'prescriptions']);

        return PdfFacade::loadView('pdf.prescription', [
            'consultation' => $consultation,
            'isOfficial' => $isOfficial,
            'generatedAt' => now(),
        ])
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4');
    }

    /**
     * File name of the prescription PDF.
     */
    public function filename(Consultation $consultation): string
    {
        return 'receta-'.Str::slug($consultation->patient->full_name).'-'.$consultation->consulted_at->format('Ymd').'.pdf';
    }
}
