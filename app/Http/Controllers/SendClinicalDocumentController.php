<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\SendClinicalDocumentRequest;
use App\Mail\ClinicalDocumentMail;
use App\Models\AuditLog;
use App\Models\ClinicalDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendClinicalDocumentController extends Controller
{
    /**
     * Email the official, signed PDF of the document to the address the doctor
     * confirms.
     */
    public function __invoke(SendClinicalDocumentRequest $request, ClinicalDocument $document): RedirectResponse
    {
        $email = $request->validated('email');
        $consultation = $document->consultation;

        try {
            Mail::to($email)->send(new ClinicalDocumentMail($document));
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'No se pudo enviar el documento por correo. Inténtalo de nuevo.');
        }

        AuditLog::record(
            AuditAction::Exported,
            $consultation,
            "Envió {$document->type->label()} {$document->number} por correo a {$email}",
            $consultation->patient,
        );

        return back()->with('success', "Se envió {$document->type->label()} {$document->number} a {$email}.");
    }
}
