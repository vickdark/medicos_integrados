<?php

namespace App\Actions\ClinicalDocuments;

use App\Actions\Verification\DocumentVerifier;
use App\Models\ClinicalDocument;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Str;

/**
 * Renders a clinical document. Like the prescription, only the copy of the
 * doctor who issued it is official; any other copy says it is not valid.
 */
class BuildClinicalDocumentPdf
{
    /**
     * Build the PDF of the document.
     */
    public function handle(ClinicalDocument $document, bool $isOfficial): PDF
    {
        $document->loadMissing([
            'consultation.patient.insurer',
            'consultation.doctor.user',
            'consultation.doctor.specialty',
            'consultation.primaryDiagnosis',
            'consultation.relatedDiagnoses',
        ]);

        return PdfFacade::loadView('pdf.clinical-document', [
            'document' => $document,
            'consultation' => $document->consultation,
            'isOfficial' => $isOfficial,
            'verification' => $isOfficial && $document->verification_code
                ? app(DocumentVerifier::class)->stamp($document->verification_code)
                : null,
            'generatedAt' => now(),
        ])
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4');
    }

    /**
     * File name of the document PDF.
     */
    public function filename(ClinicalDocument $document): string
    {
        return Str::slug($document->type->label()).'-'.$document->number.'.pdf';
    }
}
