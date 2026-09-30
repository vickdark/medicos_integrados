<?php

namespace App\Http\Controllers;

use App\Actions\ClinicalDocuments\BuildClinicalDocumentPdf;
use App\Enums\AuditAction;
use App\Http\Requests\StoreClinicalDocumentRequest;
use App\Models\AuditLog;
use App\Models\ClinicalDocument;
use App\Models\Consultation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ClinicalDocumentController extends Controller
{
    /**
     * Issue a consent, sick leave, referral or exam order from the consultation.
     */
    public function store(StoreClinicalDocumentRequest $request, Consultation $consultation): RedirectResponse
    {
        $document = $consultation->documents()->create([
            'type' => $request->documentType(),
            'data' => $request->documentData(),
            'issued_by' => $request->user()->id,
        ]);

        AuditLog::record(
            AuditAction::Created,
            $consultation,
            "Emitió {$document->type->label()} {$document->number}",
            $consultation->patient,
        );

        return back()
            ->with('success', "Se emitió: {$document->type->label()} {$document->number}.")
            ->with('issued_document_id', $document->id);
    }

    /**
     * Open the document as a PDF. The issuing doctor gets the official copy;
     * everyone else who can see the consultation gets a copy marked as not valid.
     */
    public function show(Request $request, ClinicalDocument $document, BuildClinicalDocumentPdf $pdf): Response
    {
        $consultation = $document->consultation;

        Gate::authorize('view', $consultation);

        $isOfficial = $request->user()->can('issueDocuments', $consultation);

        AuditLog::record(
            AuditAction::Exported,
            $consultation,
            $isOfficial
                ? "Generó {$document->type->label()} {$document->number} en PDF"
                : "Consultó {$document->type->label()} {$document->number} en PDF (copia sin validez)",
            $consultation->patient,
        );

        return $pdf->handle($document, $isOfficial)->stream($pdf->filename($document));
    }
}
