<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lets a patient request an appointment for themselves', function () {
    $patient = Patient::factory()->withAccount()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($patient->user)
        ->post(route('appointments.store'), [
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d\TH:i'),
            'reason' => 'Dolor de espalda',
        ])
        ->assertRedirect(route('appointments.index'))
        ->assertSessionHasNoErrors();

    $appointment = Appointment::query()->sole();

    expect($appointment->patient_id)->toBe($patient->id)
        ->and($appointment->status)->toBe(AppointmentStatus::Requested)
        ->and($appointment->created_by)->toBe($patient->user_id);
});

it('does not allow a patient to request an appointment for someone else', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->post(route('appointments.store'), [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'reason' => 'Control',
        ])
        ->assertSessionHasErrors('patient_id');

    expect(Appointment::query()->count())->toBe(0);
});

it('confirms directly the appointments scheduled by staff', function () {
    $patient = Patient::factory()->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->post(route('appointments.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'reason' => 'Control',
        ])
        ->assertSessionHasNoErrors();

    expect(Appointment::query()->sole()->status)->toBe(AppointmentStatus::Confirmed);
});

it('rejects a slot the doctor already has booked', function () {
    $appointment = Appointment::factory()->confirmed()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('appointments.store'), [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $appointment->doctor_id,
            'scheduled_at' => $appointment->scheduled_at->format('Y-m-d\TH:i'),
            'reason' => 'Control',
        ])
        ->assertSessionHasErrors('scheduled_at');
});

it('rejects appointments in the past', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('appointments.store'), [
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'scheduled_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'reason' => 'Control',
        ])
        ->assertSessionHasErrors('scheduled_at');
});

it('lets staff confirm a requested appointment', function () {
    $appointment = Appointment::factory()->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('appointments.status', $appointment), ['status' => 'confirmed'])
        ->assertSessionHasNoErrors();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Confirmed);
});

it('lets a patient cancel their appointment but not confirm it', function () {
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->for($patient)->create();

    $this->actingAs($patient->user)
        ->patch(route('appointments.status', $appointment), ['status' => 'confirmed'])
        ->assertSessionHasErrors('status');

    $this->actingAs($patient->user)
        ->patch(route('appointments.status', $appointment), ['status' => 'cancelled'])
        ->assertSessionHasNoErrors();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('forbids changing appointments of other patients or already closed ones', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->patch(route('appointments.status', Appointment::factory()->create()), ['status' => 'cancelled'])
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('appointments.status', Appointment::factory()->completed()->create()), ['status' => 'cancelled'])
        ->assertForbidden();
});

it('lists only the doctor\'s own agenda', function () {
    $doctor = Doctor::factory()->create();
    Appointment::factory()->count(2)->for($doctor)->create();
    Appointment::factory()->count(3)->create();

    $this->actingAs($doctor->user)
        ->get(route('appointments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('appointments/Index')
            ->has('appointments.data', 2)
        );
});

it('filters appointments by status', function () {
    Appointment::factory()->count(2)->create();
    Appointment::factory()->cancelled()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('appointments.index', ['status' => 'cancelled']))
        ->assertInertia(fn (Assert $page) => $page->has('appointments.data', 1));
});
