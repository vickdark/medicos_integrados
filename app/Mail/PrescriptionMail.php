<?php

namespace App\Mail;

use App\Actions\Prescriptions\BuildPrescriptionPdf;
use App\Models\Consultation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sends the official prescription PDF to the patient. It is not queued so the
 * clinical attachment is never stored in the jobs table.
 */
class PrescriptionMail extends Mailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(public Consultation $consultation) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu receta médica - '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $this->consultation->loadMissing(['patient', 'doctor.user', 'doctor.specialty']);

        return new Content(
            markdown: 'mail.prescription',
            with: [
                'patientName' => $this->consultation->patient->full_name,
                'doctorName' => $this->consultation->doctor->user->name,
                'specialty' => $this->consultation->doctor->specialty->name,
                'date' => $this->consultation->consulted_at->translatedFormat('d \d\e F \d\e Y'),
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
        $builder = app(BuildPrescriptionPdf::class);

        return [
            Attachment::fromData(
                fn (): string => $builder->handle($this->consultation, isOfficial: true)->output(),
                $builder->filename($this->consultation),
            )->withMime('application/pdf'),
        ];
    }
}
