<?php

use App\Enums\AuditAction;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('records when staff opens a clinical history', function () {
    $appointment = Appointment::factory()->create();
    $doctorUser = $appointment->doctor->user;

    $this->actingAs($doctorUser)->get(route('patients.show', $appointment->patient))->assertOk();

    $log = AuditLog::query()->sole();

    expect($log->action)->toBe(AuditAction::Viewed)
        ->and($log->user_id)->toBe($doctorUser->id)
        ->and($log->patient_id)->toBe($appointment->patient_id);
});

it('does not record reception visits, which cannot see the clinical history', function () {
    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('patients.show', Patient::factory()->create()));

    expect(AuditLog::query()->count())->toBe(0);
});

it('does not record patients reading their own history', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)->get(route('patients.show', $patient));

    expect(AuditLog::query()->count())->toBe(0);
});

it('records created consultations and which patient fields changed', function () {
    $appointment = Appointment::factory()->confirmed()->create();

    $this->actingAs($appointment->doctor->user)
        ->post(route('consultations.store', $appointment->patient), [
            'reason' => 'Control',
            'diagnosis' => 'Sano',
            'primary_diagnosis_id' => Diagnosis::factory()->create()->id,
            'diagnosis_type' => '1',
        ]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('patients.update', $appointment->patient), [
            'first_name' => $appointment->patient->first_name,
            'last_name' => $appointment->patient->last_name,
            'document_number' => $appointment->patient->document_number,
            'document_type' => 'CC',
            'phone' => '000',
        ]);

    expect(AuditLog::query()->where('action', AuditAction::Created)->where('auditable_type', (new Consultation)->getMorphClass())->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::Updated)->sole()->description)->toContain('phone');
});

it('shows the audit trail to the admin filtered by patient', function () {
    $patient = Patient::factory()->create();
    AuditLog::factory()->count(2)->create(['patient_id' => $patient->id, 'auditable_id' => $patient->id]);
    AuditLog::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('audit-logs.index', ['patient_id' => $patient->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audit/Index')
            ->has('logs.data', 2)
            ->where('patient.full_name', $patient->full_name)
        );
});

it('forbids the audit trail to non admins', function () {
    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('audit-logs.index'))
        ->assertForbidden();
});
