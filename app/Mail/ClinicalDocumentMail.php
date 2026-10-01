<?php

namespace App\Mail;

use App\Actions\ClinicalDocuments\BuildClinicalDocumentPdf;
use App\Models\ClinicalDocument;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sends the official PDF of a clinical document to the patient. It is not
 * queued so the clinical attachment is never stored in the jobs table.
 */
class ClinicalDocumentMail extends Mailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(public ClinicalDocument $document) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->document->type->label().' - '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $consultation = $this->document->consultation->loadMissing(['patient', 'doctor.user', 'doctor.specialty']);

        return new Content(
            markdown: 'mail.clinical-document',
            with: [
                'patientName' => $consultation->patient->full_name,
                'doctorName' => $consultation->doctor->user->name,
                'specialty' => $consultation->doctor->specialty->name,
                'documentName' => $this->document->type->label(),
                'number' => $this->document->number,
                'date' => $consultation->consulted_at->translatedFormat('d \d\e F \d\e Y'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $builder = app(BuildClinicalDocumentPdf::class);

        return [
            Attachment::fromData(
                fn (): string => $builder->handle($this->document, isOfficial: true)->output(),
                $builder->filename($this->document),
            )->withMime('application/pdf'),
        ];
    }
}
