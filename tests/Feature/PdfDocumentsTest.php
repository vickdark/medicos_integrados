<?php

use App\Enums\AuditAction;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
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
