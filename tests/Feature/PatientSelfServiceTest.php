<?php

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the patient a contact-only edit form', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->get(route('patients.edit', $patient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('patients/Edit')
            ->where('contactOnly', true)
        );
});

it('lets the patient update only their contact details', function () {
    $patient = Patient::factory()->withAccount()->create([
        'first_name' => 'Original',
        'allergies' => 'Polen',
    ]);
    Consultation::factory()->create(['patient_id' => $patient->id]);

    $this->actingAs($patient->user)
        ->put(route('patients.update', $patient), [
            'phone' => '999 888 777',
            'address' => 'Av. Siempre Viva 742',
            'first_name' => 'Cambiado',
            'allergies' => '',
        ])
        ->assertRedirect(route('patients.show', $patient))
        ->assertSessionHasNoErrors();

    $patient->refresh();

    expect($patient->phone)->toBe('999 888 777')
        ->and($patient->address)->toBe('Av. Siempre Viva 742')
        ->and($patient->first_name)->toBe('Original')
        ->and($patient->allergies)->toBe('Polen');
});

it('forbids a patient from editing someone else\'s record', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->put(route('patients.update', Patient::factory()->create()), ['phone' => '123'])
        ->assertForbidden();
});

it('lets the patient provide their basic data until the first consultation', function () {
    $patient = Patient::factory()->withAccount()->create([
        'document_number' => null,
        'birth_date' => null,
        'gender' => null,
        'blood_type' => null,
        'allergies' => 'Polen',
        'chronic_conditions' => 'Hipertensión',
    ]);

    $this->actingAs($patient->user)
        ->get(route('patients.edit', $patient))
        ->assertInertia(fn (Assert $page) => $page->where('preliminaryEditable', true));

    $this->actingAs($patient->user)
        ->put(route('patients.update', $patient), [
            'document_number' => '45678901',
            'document_type' => 'CC',
            'birth_date' => '1990-05-20',
            'gender' => 'female',
            'blood_type' => 'O+',
            'phone' => '999 111 222',
            'allergies' => 'Penicilina',
            'chronic_conditions' => 'Asma',
            'first_name' => 'Ignorado',
        ])
        ->assertRedirect(route('patients.show', $patient))
        ->assertSessionHasNoErrors();

    $patient->refresh();

    expect($patient->document_number)->toBe('45678901')
        ->and($patient->birth_date->toDateString())->toBe('1990-05-20')
        ->and($patient->gender->value)->toBe('female')
        ->and($patient->blood_type)->toBe('O+')
        ->and($patient->allergies)->toBe('Polen')
        ->and($patient->chronic_conditions)->toBe('Hipertensión')
        ->and($patient->first_name)->not->toBe('Ignorado');
});

it('requires the basic data while the patient can still provide it', function () {
    $patient = Patient::factory()->withAccount()->create(['document_number' => null, 'birth_date' => null, 'gender' => null, 'phone' => null]);

    $this->actingAs($patient->user)
        ->put(route('patients.update', $patient), ['address' => 'Calle 1'])
        ->assertSessionHasErrors(['document_number', 'birth_date', 'gender', 'phone']);
});

it('always shows the reminder on the dashboard without redirecting', function () {
    $patient = Patient::factory()->withAccount()->create(['document_number' => null, 'birth_date' => null]);

    $this->actingAs($patient->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('profileReminder.patient_id', $patient->id)
            ->where('profileReminder.incomplete', true)
            ->where('profileReminder.locked', false)
        );
});

it('keeps reminding to update the data when the profile is complete', function () {
    $patient = Patient::factory()->withAccount()->create();
    $patient->user->forceFill(['login_count' => 5])->save();

    $this->actingAs($patient->user->fresh())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('profileReminder.incomplete', false));
});

it('counts every sign-in of the account', function () {
    $user = User::factory()->create(['email' => 'contador@example.com']);

    $this->post(route('login.store'), ['email' => 'contador@example.com', 'password' => 'password']);
    auth()->logout();
    $this->post(route('login.store'), ['email' => 'contador@example.com', 'password' => 'password']);

    expect($user->fresh()->login_count)->toBe(2);
});

it('locks the basic data once a doctor has attended the patient', function () {
    $patient = Patient::factory()->withAccount()->create(['document_number' => '111', 'blood_type' => 'A+']);
    Consultation::factory()->create(['patient_id' => $patient->id]);

    $this->actingAs($patient->user)
        ->get(route('patients.edit', $patient))
        ->assertInertia(fn (Assert $page) => $page->where('preliminaryEditable', false));

    $this->actingAs($patient->user)
        ->put(route('patients.update', $patient), ['phone' => '555', 'document_number' => '999', 'blood_type' => 'O-'])
        ->assertSessionHasNoErrors();

    $patient->refresh();

    expect($patient->phone)->toBe('555')
        ->and($patient->document_number)->toBe('111')
        ->and($patient->blood_type)->toBe('A+');

    $this->actingAs($patient->user)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.fill_preliminary', false)
            ->where('can.preliminary_locked', true)
        );
});

it('still lets staff edit the medical data after the first consultation', function () {
    $patient = Patient::factory()->create();
    Consultation::factory()->create(['patient_id' => $patient->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('patients.update', $patient), [
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'allergies' => 'Corregido',
        ])
        ->assertSessionHasNoErrors();

    expect($patient->fresh()->allergies)->toBe('Corregido');
});
