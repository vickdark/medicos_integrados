<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lets the doctor record a consultation with prescriptions and completes the appointment', function () {
    $appointment = Appointment::factory()->confirmed()->create();

    $response = $this->actingAs($appointment->doctor->user)
        ->post(route('consultations.store', $appointment->patient), [
            'appointment_id' => $appointment->id,
            'reason' => 'Dolor de garganta',
            'diagnosis' => 'Faringitis aguda',
            'primary_diagnosis_id' => Diagnosis::factory()->create()->id,
            'diagnosis_type' => '1',
            'treatment' => 'Reposo e hidratación',
            'blood_pressure' => '120/80',
            'temperature_c' => '38.2',
            'prescriptions' => [
                ['medication' => 'Amoxicilina', 'dosage' => '500 mg', 'frequency' => 'Cada 8 horas', 'duration' => '7 días'],
            ],
        ]);

    $consultation = Consultation::query()->sole();

    $response->assertRedirect(route('consultations.show', $consultation));

    expect($consultation->doctor_id)->toBe($appointment->doctor_id)
        ->and($consultation->diagnosis)->toBe('Faringitis aguda')
        ->and($consultation->getRawOriginal('diagnosis'))->not->toBe('Faringitis aguda')
        ->and($consultation->prescriptions)->toHaveCount(1)
        ->and($appointment->refresh()->status)->toBe(AppointmentStatus::Completed);
});

it('validates the consultation data', function () {
    $appointment = Appointment::factory()->confirmed()->create();

    $this->actingAs($appointment->doctor->user)
        ->post(route('consultations.store', $appointment->patient), [
            'blood_pressure' => 'alta',
            'prescriptions' => [['medication' => '']],
        ])
        ->assertSessionHasErrors(['reason', 'diagnosis', 'blood_pressure', 'prescriptions.0.medication', 'prescriptions.0.dosage']);
});

it('does not allow linking an appointment of another patient', function () {
    $appointment = Appointment::factory()->confirmed()->create();
    $foreignAppointment = Appointment::factory()->for($appointment->doctor)->create();

    $this->actingAs($appointment->doctor->user)
        ->post(route('consultations.store', $appointment->patient), [
            'appointment_id' => $foreignAppointment->id,
            'reason' => 'Control',
            'diagnosis' => 'Sano',
            'primary_diagnosis_id' => Diagnosis::factory()->create()->id,
            'diagnosis_type' => '1',
        ])
        ->assertSessionHasErrors('appointment_id');
});

it('forbids recording consultations to staff that is not the treating doctor', function (User $user) {
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->get(route('consultations.create', $patient))
        ->assertForbidden();
})->with([
    'unrelated doctor' => fn () => Doctor::factory()->create()->user,
    'receptionist' => fn () => User::factory()->receptionist()->create(),
    'admin' => fn () => User::factory()->admin()->create(),
]);

it('lets the patient read their consultation without internal notes', function () {
    $patient = Patient::factory()->withAccount()->create();
    $consultation = Consultation::factory()->for($patient)->create(['notes' => 'Nota privada']);

    $this->actingAs($patient->user)
        ->get(route('consultations.show', $consultation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('consultations/Show')
            ->where('consultation.diagnosis', $consultation->diagnosis)
            ->missing('consultation.notes')
        );
});

it('forbids reading consultations of other patients or from reception', function () {
    $consultation = Consultation::factory()->create();
    $otherPatient = Patient::factory()->withAccount()->create();

    $this->actingAs($otherPatient->user)
        ->get(route('consultations.show', $consultation))
        ->assertForbidden();

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('consultations.show', $consultation))
        ->assertForbidden();
});

it('opens the pending charge of the appointment when the consultation completes it', function () {
    $appointment = Appointment::factory()->confirmed()->create();

    $this->actingAs($appointment->doctor->user)
        ->post(route('consultations.store', $appointment->patient), [
            'appointment_id' => $appointment->id,
            'reason' => 'Control',
            'diagnosis' => 'Sano',
            'primary_diagnosis_id' => Diagnosis::factory()->create()->id,
            'diagnosis_type' => '1',
        ]);

    expect(Payment::query()->where('appointment_id', $appointment->id)->sole()->status->value)->toBe('pending');
});

it('shows a friendly error page instead of a bare 403 when reception opens a consultation', function () {
    $appointment = Appointment::factory()->completed()->create();
    $consultation = Consultation::factory()->create(['patient_id' => $appointment->patient_id, 'doctor_id' => $appointment->doctor_id, 'appointment_id' => $appointment->id]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('consultations.show', $consultation))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page->component('Error')->where('status', 403));
});

it('only offers the consultation link to roles that can read it', function () {
    $appointment = Appointment::factory()->completed()->create();
    Consultation::factory()->create(['patient_id' => $appointment->patient_id, 'doctor_id' => $appointment->doctor_id, 'appointment_id' => $appointment->id]);
    $feed = route('appointments.calendar', [
        'from' => $appointment->scheduled_at->copy()->subDay()->toDateString(),
        'to' => $appointment->scheduled_at->copy()->addDay()->toDateString(),
    ]);

    $this->actingAs(User::factory()->receptionist()->create())->getJson($feed)
        ->assertJsonPath('data.0.can.view_consultation', false);
    $this->actingAs($appointment->doctor->user)->getJson($feed)
        ->assertJsonPath('data.0.can.view_consultation', true);
});
