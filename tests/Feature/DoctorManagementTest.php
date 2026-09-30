<?php

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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

it('shows the profile of a doctor to the administrator', function () {
    $doctor = Doctor::factory()->create(['consultation_fee' => 60, 'slot_minutes' => 20, 'bio' => 'Reseña del médico']);
    Appointment::factory()->confirmed()->create(['doctor_id' => $doctor->id, 'scheduled_at' => now()->addDay()]);
    Consultation::factory()->create(['doctor_id' => $doctor->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('doctors.show', $doctor))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('doctors/Show')
            ->where('doctor.id', $doctor->id)
            ->where('doctor.user_id', $doctor->user_id)
            ->where('doctor.slot_minutes', 20)
            ->where('bio', 'Reseña del médico')
            ->where('stats.upcoming', 1)
            ->where('stats.consultations', 1)
            ->where('can.edit', true)
        );
});

it('does not show the doctor profile page to anyone but the administrator', function () {
    $doctor = Doctor::factory()->create();

    foreach (['receptionist', 'patient'] as $state) {
        $this->actingAs(User::factory()->{$state}()->create())
            ->get(route('doctors.show', $doctor))
            ->assertForbidden();
    }

    $this->actingAs($doctor->user)->get(route('doctors.show', $doctor))->assertForbidden();
});

it('gives the doctors table what it needs to link to the profile and the edit form', function () {
    $doctor = Doctor::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('doctors.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('doctors.data.0.id', $doctor->id)
            ->where('doctors.data.0.user_id', $doctor->user_id)
            ->where('doctors.data.0.is_active', true)
        );
});
