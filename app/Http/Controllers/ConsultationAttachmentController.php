<?php

namespace App\Http\Controllers;

use App\Enums\AttachmentStatus;
use App\Enums\AuditAction;
use App\Http\Requests\ChangeAttachmentStatusRequest;
use App\Http\Requests\StoreConsultationAttachmentRequest;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\ConsultationAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsultationAttachmentController extends Controller
{
    /**
     * Upload a file (lab results, images…) to the consultation.
     */
    public function store(StoreConsultationAttachmentRequest $request, Consultation $consultation): RedirectResponse
    {
        $file = $request->file('file');

        $attachment = $consultation->attachments()->create([
            'uploaded_by' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'path' => $file->store("consultations/{$consultation->id}", ConsultationAttachment::DISK),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'description' => $request->validated('description'),
        ]);

        AuditLog::record(AuditAction::Uploaded, $attachment, "Subió el archivo «{$attachment->original_name}»", $consultation->patient);

        return back()->with('success', 'Archivo adjuntado.');
    }

    /**
     * Download the attachment if the user can see the consultation. Voided files
     * stay available to the clinic staff but are no longer shown to the patient.
     */
    public function show(Request $request, ConsultationAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment->consultation);

        abort_if(! $attachment->isActive() && ! $request->user()->isStaff(), 404);

        AuditLog::record(AuditAction::Downloaded, $attachment, "Descargó el archivo «{$attachment->original_name}»", $attachment->consultation->patient);

        return Storage::disk(ConsultationAttachment::DISK)->download($attachment->path, $attachment->original_name);
    }

    /**
     * Correct the attachment with a new file or void it, always with a reason.
     * The original file is kept as part of the record and the reason stays
     * internal: the patient only sees the active version.
     */
    public function changeStatus(ChangeAttachmentStatusRequest $request, ConsultationAttachment $attachment): RedirectResponse
    {
        $user = $request->user();
        $reason = $request->validated('reason');
        $patient = $attachment->consultation->patient;

        if ($request->status() === AttachmentStatus::Corrected) {
            $replacement = $attachment->correctWith($request->file('file'), $user, $reason, $request->validated('description'));

            AuditLog::record(AuditAction::Updated, $attachment, "Corrigió el archivo «{$attachment->original_name}» con «{$replacement->original_name}»", $patient);

            return back()->with('success', 'Archivo corregido. La versión anterior se conserva en la historia clínica.');
        }

        $attachment->void($user, $reason);

        AuditLog::record(AuditAction::Updated, $attachment, "Anuló el archivo «{$attachment->original_name}»", $patient);

        return back()->with('success', 'Archivo anulado. Se conserva en la historia clínica con el motivo.');
    }
}
