<?php

use App\Models\Appointment;
use App\Models\Medication;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lists the catalog for admins and doctors', function (string $state) {
    Medication::factory()->count(3)->create();

    $this->actingAs(User::factory()->{$state}()->create())
        ->get(route('medications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('medications/Index')
            ->has('medications.data', 3)
        );
})->with(['admin', 'doctor']);

it('searches medications by name, presentation or concentration', function () {
    Medication::factory()->create(['name' => 'Ibuprofeno', 'presentation' => 'Tabletas', 'concentration' => '400 mg']);
    Medication::factory()->create(['name' => 'Salbutamol', 'presentation' => 'Inhalador', 'concentration' => '100 mcg']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('medications.index', ['search' => 'inhal']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('medications.data', 1)
            ->where('medications.data.0.name', 'Salbutamol')
        );
});

it('keeps the catalog away from reception and patients', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->get(route('medications.index'))->assertForbidden();
    $this->actingAs($user)->post(route('medications.store'), ['name' => 'Aspirina'])->assertForbidden();
})->with(['receptionist', 'patient']);

it('creates, updates and deletes medications', function () {
    $doctor = User::factory()->doctor()->create();

    $this->actingAs($doctor)
        ->post(route('medications.store'), [
            'name' => 'Paracetamol',
            'presentation' => 'Tabletas',
            'concentration' => '500 mg',
        ])
        ->assertRedirect(route('medications.index'));

    $medication = Medication::query()->sole();

    expect($medication->label)->toBe('Paracetamol 500 mg · Tabletas');

    $this->actingAs($doctor)
        ->put(route('medications.update', $medication), ['name' => 'Paracetamol', 'presentation' => 'Jarabe', 'concentration' => '120 mg/5 ml'])
        ->assertRedirect(route('medications.index'));

    expect($medication->refresh()->presentation)->toBe('Jarabe');

    $this->actingAs($doctor)->delete(route('medications.destroy', $medication))->assertRedirect(route('medications.index'));

    expect(Medication::query()->count())->toBe(0);
});

it('rejects the same medication twice but allows other presentations', function () {
    Medication::factory()->create(['name' => 'Amoxicilina', 'presentation' => 'Cápsulas', 'concentration' => '500 mg']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('medications.store'), ['name' => 'Amoxicilina', 'presentation' => 'Cápsulas', 'concentration' => '500 mg'])
        ->assertSessionHasErrors('name');

    $this->actingAs($admin)
        ->post(route('medications.store'), ['name' => 'Amoxicilina', 'presentation' => 'Jarabe', 'concentration' => '250 mg'])
        ->assertSessionHasNoErrors();

    expect(Medication::query()->count())->toBe(2);
});

it('keeps the doctor on the consultation form when creating a medication inline', function () {
    $this->actingAs(User::factory()->doctor()->create())
        ->from(route('dashboard'))
        ->post(route('medications.store'), ['name' => 'Aspirina', 'inline' => '1'])
        ->assertRedirect(route('dashboard'));

    expect(Medication::query()->sole()->name)->toBe('Aspirina');
});

it('offers the catalog on the consultation form', function () {
    Medication::factory()->create(['name' => 'Omeprazol', 'presentation' => 'Cápsulas', 'concentration' => '20 mg']);
    $appointment = Appointment::factory()->confirmed()->create();

    $this->actingAs($appointment->doctor->user)
        ->get(route('consultations.create', $appointment->patient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('medications', 1)
            ->where('medications.0.label', 'Omeprazol 20 mg · Cápsulas')
        );
});
