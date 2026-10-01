<?php

use App\Enums\AuditAction;
use App\Mail\PrescriptionMail;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

/**
 * Capture the data the PDF view is rendered with.
 *
 * @return array{data: array<string, mixed>|null}
 */
function capturePdfData(string $view): object
{
    $captured = (object) ['data' => null];

    View::composer($view, function ($composed) use ($captured): void {
        $captured->data = $composed->getData();
    });

    return $captured;
}

it('downloads the whole clinical history when no dates are given', function () {
    $patient = Patient::factory()->withAccount()->create();
    Consultation::factory()->count(3)->create(['patient_id' => $patient->id]);
    $captured = capturePdfData('pdf.clinical-history');

    $this->actingAs($patient->user)
        ->get(route('patients.history', $patient))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($captured->data['consultations'])->toHaveCount(3)
        ->and($captured->data['period'])->toBe('Historial completo');
});

it('limits the clinical history to the requested date range', function () {
    $patient = Patient::factory()->withAccount()->create();
    $doctor = Doctor::factory()->create();
    Consultation::factory()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'consulted_at' => '2026-01-10 09:00:00']);
    $inside = Consultation::factory()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'consulted_at' => '2026-03-15 10:00:00']);
    Consultation::factory()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'consulted_at' => '2026-06-01 11:00:00']);
    $captured = capturePdfData('pdf.clinical-history');

    $this->actingAs($doctor->user)
        ->get(route('patients.history', ['patient' => $patient, 'from' => '2026-03-01', 'to' => '2026-03-31']))
        ->assertOk();

    expect($captured->data['consultations']->pluck('id')->all())->toBe([$inside->id])
        ->and($captured->data['period'])->toBe('Del 01/03/2026 al 31/03/2026');
});

it('includes the last day of the range and supports open-ended ranges', function () {
    $patient = Patient::factory()->withAccount()->create();
    Consultation::factory()->create(['patient_id' => $patient->id, 'consulted_at' => '2026-03-31 18:30:00']);
    Consultation::factory()->create(['patient_id' => $patient->id, 'consulted_at' => '2026-02-01 08:00:00']);
    $captured = capturePdfData('pdf.clinical-history');

    $this->actingAs($patient->user)
        ->get(route('patients.history', ['patient' => $patient, 'to' => '2026-03-31']))
        ->assertOk();

    expect($captured->data['consultations'])->toHaveCount(2)
        ->and($captured->data['period'])->toBe('Hasta el 31/03/2026');
});

it('validates the range of the clinical history', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->get(route('patients.history', ['patient' => $patient, 'from' => '2026-05-10', 'to' => '2026-05-01']))
        ->assertSessionHasErrors('to');
});

it('does not let anyone without access download a clinical history', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('patients.history', $patient))->assertForbidden();
    $this->actingAs(Doctor::factory()->create()->user)
        ->get(route('patients.history', $patient))->assertForbidden();
    $this->actingAs(Patient::factory()->withAccount()->create()->user)
        ->get(route('patients.history', $patient))->assertForbidden();
});

it('records the clinical history download in the audit trail', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('patients.history', ['patient' => $patient, 'from' => '2026-01-01', 'to' => '2026-01-31']))
        ->assertOk();

    expect(AuditLog::query()->where('action', AuditAction::Exported)->where('description', 'like', '%historia clínica%01/01/2026 al 31/01/2026%')->exists())->toBeTrue();
});

it('hides private notes from the patient but keeps them for staff', function () {
    $patient = Patient::factory()->withAccount()->create();
    Consultation::factory()->create(['patient_id' => $patient->id]);
    $captured = capturePdfData('pdf.clinical-history');

    $this->actingAs($patient->user)->get(route('patients.history', $patient));
    expect($captured->data['includeNotes'])->toBeFalse();

    $this->actingAs(User::factory()->admin()->create())->get(route('patients.history', $patient));
    expect($captured->data['includeNotes'])->toBeTrue();
});

it('downloads the invoice of a paid payment', function () {
    $appointment = Appointment::factory()->completed()->create();
    $payment = Payment::factory()->create(['appointment_id' => $appointment->id, 'patient_id' => $appointment->patient_id]);
    $captured = capturePdfData('pdf.invoice');

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('payments.invoice', $payment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($captured->data['number'])->toBe('F-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT));
});

it('lets the patient download the invoice of their own payment only', function () {
    $owner = Patient::factory()->withAccount()->create();
    $own = Payment::factory()->create(['patient_id' => $owner->id]);
    $foreign = Payment::factory()->create();

    $this->actingAs($owner->user)->get(route('payments.invoice', $own))->assertOk();
    $this->actingAs($owner->user)->get(route('payments.invoice', $foreign))->assertForbidden();
});

it('does not invoice payments that are pending', function () {
    $payment = Payment::factory()->pending()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('payments.invoice', $payment))
        ->assertForbidden();
});

it('tells the interface whether the invoice can be downloaded', function () {
    $paid = Payment::factory()->create();
    Payment::factory()->pending()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('payments.index'))
        ->assertInertia(fn ($page) => $page
            ->where('payments.data', fn ($rows) => collect($rows)->firstWhere('id', $paid->id)['can']['download_invoice'] === true
                && collect($rows)->where('can.download_invoice', true)->count() === 1));
});

it('lets the doctor who wrote the consultation open the prescription PDF', function () {
    $consultation = Consultation::factory()->create();
    Prescription::factory()->count(2)->create(['consultation_id' => $consultation->id]);
    $captured = capturePdfData('pdf.prescription');

    $this->actingAs($consultation->doctor->user)
        ->get(route('consultations.prescription', $consultation))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($captured->data['consultation']->prescriptions)->toHaveCount(2)
        ->and(AuditLog::query()->where('description', 'like', '%receta%')->exists())->toBeTrue();
});

it('gives the administrator a reference copy marked as not valid', function () {
    $consultation = Consultation::factory()->create();
    Prescription::factory()->create(['consultation_id' => $consultation->id]);
    $captured = capturePdfData('pdf.prescription');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('consultations.prescription', $consultation))
        ->assertOk();

    expect($captured->data['isOfficial'])->toBeFalse()
        ->and(AuditLog::query()->where('description', 'like', '%sin validez%')->exists())->toBeTrue();

    $this->actingAs($consultation->doctor->user)->get(route('consultations.prescription', $consultation));

    expect($captured->data['isOfficial'])->toBeTrue();
});

it('shows the not valid notice in the rendered prescription of the administrator', function () {
    $consultation = Consultation::factory()->create();
    Prescription::factory()->create(['consultation_id' => $consultation->id]);
    $consultation->load(['patient', 'doctor.user', 'doctor.specialty', 'prescriptions']);

    $copy = view('pdf.prescription', ['consultation' => $consultation, 'isOfficial' => false, 'generatedAt' => now()])->render();
    $official = view('pdf.prescription', ['consultation' => $consultation, 'isOfficial' => true, 'generatedAt' => now()])->render();

    expect($copy)->toContain('Este documento no es válido')->toContain('consulte con el médico responsable')
        ->and($official)->not->toContain('Este documento no es válido');
});

it('does not let anyone else open the prescription', function () {
    $patient = Patient::factory()->withAccount()->create();
    $consultation = Consultation::factory()->create(['patient_id' => $patient->id]);
    Prescription::factory()->create(['consultation_id' => $consultation->id]);

    $this->actingAs($patient->user)->get(route('consultations.prescription', $consultation))->assertForbidden();
    $this->actingAs(User::factory()->receptionist()->create())->get(route('consultations.prescription', $consultation))->assertForbidden();
    $this->actingAs(Doctor::factory()->create()->user)->get(route('consultations.prescription', $consultation))->assertForbidden();
});

it('returns not found when the consultation has no prescriptions', function () {
    $consultation = Consultation::factory()->create();

    $this->actingAs($consultation->doctor->user)
        ->get(route('consultations.prescription', $consultation))
        ->assertNotFound();
});

it('offers the prescription button only to the prescribing doctor', function () {
    $consultation = Consultation::factory()->create();
    Prescription::factory()->create(['consultation_id' => $consultation->id]);

    $this->actingAs($consultation->doctor->user)
        ->get(route('consultations.show', $consultation))
        ->assertInertia(fn ($page) => $page->where('can.email_prescription', true)->where('patientEmail', $consultation->patient->email));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('consultations.show', $consultation))
        ->assertInertia(fn ($page) => $page
            ->where('can.download_prescription', true)
            ->where('can.email_prescription', false)
            ->where('patientEmail', null));
});

it('emails the official prescription to the address the doctor confirms', function () {
    Mail::fake();
    $consultation = Consultation::factory()->for(Doctor::factory()->signed())->create();
    Prescription::factory()->create(['consultation_id' => $consultation->id]);

    $this->actingAs($consultation->doctor->user)
        ->post(route('consultations.prescription.email', $consultation), ['email' => 'otro@example.com'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Mail::assertSent(PrescriptionMail::class, fn (PrescriptionMail $mail) => $mail->hasTo('otro@example.com')
        && $mail->consultation->is($consultation));

    expect(AuditLog::query()->where('description', 'like', '%por correo a otro@example.com%')->exists())->toBeTrue();
});

it('builds the prescription email with a PDF attachment', function () {
    $consultation = Consultation::factory()->create();
    Prescription::factory()->create(['consultation_id' => $consultation->id]);

    $attachments = (new PrescriptionMail($consultation))->attachments();

    expect($attachments)->toHaveCount(1);
});

it('validates the email address of the prescription', function () {
    Mail::fake();
    $consultation = Consultation::factory()->create();
    Prescription::factory()->create(['consultation_id' => $consultation->id]);

    $this->actingAs($consultation->doctor->user)
        ->post(route('consultations.prescription.email', $consultation), ['email' => 'no-es-un-correo'])
        ->assertSessionHasErrors('email');

    $this->actingAs($consultation->doctor->user)
        ->post(route('consultations.prescription.email', $consultation), [])
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});

it('only lets the prescribing doctor email the prescription', function () {
    Mail::fake();
    $consultation = Consultation::factory()->create();
    Prescription::factory()->create(['consultation_id' => $consultation->id]);
    $body = ['email' => 'paciente@example.com'];

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('consultations.prescription.email', $consultation), $body)->assertForbidden();
    $this->actingAs(Doctor::factory()->create()->user)
        ->post(route('consultations.prescription.email', $consultation), $body)->assertForbidden();
    $this->actingAs($consultation->patient->user ?? User::factory()->create())
        ->post(route('consultations.prescription.email', $consultation), $body)->assertForbidden();

    Mail::assertNothingSent();
});
