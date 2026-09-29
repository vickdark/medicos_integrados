<?php

use App\Models\Patient;
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
