<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\StoreConsultationAttachmentRequest;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\ConsultationAttachment;
use Illuminate\Http\RedirectResponse;
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
     * Download the attachment if the user can see the consultation.
     */
    public function show(ConsultationAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment->consultation);

        AuditLog::record(AuditAction::Downloaded, $attachment, "Descargó el archivo «{$attachment->original_name}»", $attachment->consultation->patient);

        return Storage::disk(ConsultationAttachment::DISK)->download($attachment->path, $attachment->original_name);
    }

    /**
     * Remove the attachment and its stored file.
     */
    public function destroy(ConsultationAttachment $attachment): RedirectResponse
    {
        Gate::authorize('manageAttachments', $attachment->consultation);

        AuditLog::record(AuditAction::Deleted, $attachment, "Eliminó el archivo «{$attachment->original_name}»", $attachment->consultation->patient);

        $attachment->delete();

        return back()->with('success', 'Archivo eliminado.');
    }
}
