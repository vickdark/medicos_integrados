<?php

use App\Enums\UserRole;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;

it('lets the admin register a doctor with their user account', function () {
    $specialty = Specialty::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('doctors.store'), [
            'name' => 'Dr. Mario Ruiz',
            'email' => 'mario@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'specialty_id' => $specialty->id,
            'license_number' => 'CMP-12345',
            'consultation_fee' => '60',
        ])
        ->assertRedirect(route('doctors.index'))
        ->assertSessionHasNoErrors();

    $doctor = Doctor::query()->with('user')->sole();

    expect($doctor->user->role)->toBe(UserRole::Doctor)
        ->and($doctor->user->email)->toBe('mario@example.com')
        ->and($doctor->specialty_id)->toBe($specialty->id);
});

it('forbids non admins from managing doctors', function (string $factoryState) {
    $this->actingAs(User::factory()->{$factoryState}()->create())
        ->get(route('doctors.index'))
        ->assertForbidden();
})->with(['receptionist', 'doctor', 'patient']);
