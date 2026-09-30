<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lets clinic staff list and search patients', function () {
    Patient::factory()->create(['first_name' => 'Rosa', 'last_name' => 'Quispe']);
    Patient::factory()->count(3)->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('patients.index', ['search' => 'Quispe']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('patients/Index')
            ->has('patients.data', 1)
            ->where('patients.data.0.full_name', 'Rosa Quispe')
        );
});

it('forbids patients from listing other patients', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->get(route('patients.index'))
        ->assertForbidden();
});

it('lists for a doctor only the patients they treat', function () {
    $doctor = Doctor::factory()->create();
    Appointment::factory()->for($doctor)->create();
    Patient::factory()->count(2)->create();

    $this->actingAs($doctor->user)
        ->get(route('patients.index'))
        ->assertInertia(fn (Assert $page) => $page->has('patients.data', 1));
});

it('registers a patient with encrypted clinical data', function () {
    $this->actingAs(User::factory()->receptionist()->create())
        ->post(route('patients.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Torres',
            'document_number' => '12345678',
            'email' => 'ana@example.com',
            'birth_date' => '1990-05-10',
            'gender' => 'female',
            'blood_type' => 'O+',
            'allergies' => 'Penicilina',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $patient = Patient::query()->where('document_number', '12345678')->sole();

    expect($patient->allergies)->toBe('Penicilina')
        ->and($patient->getRawOriginal('allergies'))->not->toBe('Penicilina');
});

it('validates the patient data', function () {
    Patient::factory()->create(['document_number' => '11111111']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('patients.store'), [
            'first_name' => '',
            'document_number' => '11111111',
            'birth_date' => now()->addDay()->toDateString(),
            'blood_type' => 'Z+',
        ])
        ->assertSessionHasErrors(['first_name', 'last_name', 'document_number', 'birth_date', 'blood_type']);
});

it('updates a patient record', function () {
    $patient = Patient::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('patients.update', $patient), [
            'first_name' => 'Nuevo',
            'last_name' => 'Nombre',
            'document_number' => $patient->document_number,
        ])
        ->assertRedirect(route('patients.show', $patient));

    expect($patient->refresh()->full_name)->toBe('Nuevo Nombre');
});

it('hides the clinical history from reception', function () {
    $patient = Patient::factory()->create(['allergies' => 'Polen']);

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('patients/Show')
            ->missing('patient.allergies')
            ->where('consultations', null)
            ->where('can.view_medical_history', false)
        );
});

it('lets a patient see their own record but not someone else\'s', function () {
    $patient = Patient::factory()->withAccount()->create(['allergies' => 'Polen']);
    $otherPatient = Patient::factory()->create();

    $this->actingAs($patient->user)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('patient.allergies', 'Polen')
            ->where('can.update', true)
            ->where('can.view_audit_trail', false)
        );

    $this->actingAs($patient->user)
        ->get(route('patients.show', $otherPatient))
        ->assertForbidden();
});

it('forbids a doctor from opening a patient they do not treat', function () {
    $doctor = Doctor::factory()->create();

    $this->actingAs($doctor->user)
        ->get(route('patients.show', Patient::factory()->create()))
        ->assertForbidden();
});

it('tells the patients table which actions each row allows', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    Appointment::factory()->create(['doctor_id' => $doctor->id, 'patient_id' => $patient->id]);

    $this->actingAs($doctor->user)
        ->get(route('patients.index'))
        ->assertInertia(fn ($page) => $page
            ->where('patients.data.0.can.update', true)
            ->where('patients.data.0.can.create_consultation', true));

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('patients.index'))
        ->assertInertia(fn ($page) => $page
            ->where('patients.data.0.can.update', true)
            ->where('patients.data.0.can.create_consultation', false));
});
