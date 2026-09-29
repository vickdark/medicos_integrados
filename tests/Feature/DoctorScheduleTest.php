<?php

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

it('lets the doctor manage their own office hours', function () {
    $doctor = Doctor::factory()->create();

    $this->actingAs($doctor->user)
        ->get(route('schedules.index', $doctor))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('doctors/Schedules'));

    $this->actingAs($doctor->user)
        ->post(route('schedules.store', $doctor), [
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '12:00',
        ])
        ->assertSessionHasNoErrors();

    expect($doctor->schedules()->count())->toBe(1);
});

it('forbids managing office hours of another doctor', function () {
    $doctor = Doctor::factory()->create();
    $otherDoctor = Doctor::factory()->create();

    $this->actingAs($doctor->user)
        ->get(route('schedules.index', $otherDoctor))
        ->assertForbidden();

    $this->actingAs(User::factory()->receptionist()->create())
        ->post(route('schedules.store', $otherDoctor), [
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '12:00',
        ])
        ->assertForbidden();
});

it('validates overlapping and inverted blocks', function () {
    $doctor = Doctor::factory()->create();
    DoctorSchedule::factory()->for($doctor)->create(['day_of_week' => 2, 'starts_at' => '08:00', 'ends_at' => '12:00']);

    $this->actingAs($doctor->user)
        ->post(route('schedules.store', $doctor), ['day_of_week' => 2, 'starts_at' => '11:00', 'ends_at' => '14:00'])
        ->assertSessionHasErrors('starts_at');

    $this->actingAs($doctor->user)
        ->post(route('schedules.store', $doctor), ['day_of_week' => 3, 'starts_at' => '14:00', 'ends_at' => '10:00'])
        ->assertSessionHasErrors('ends_at');
});

it('lets the admin remove a block', function () {
    $schedule = DoctorSchedule::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('schedules.destroy', $schedule))
        ->assertRedirect();

    expect(DoctorSchedule::query()->count())->toBe(0);
});

it('only accepts appointments within the doctor office hours', function () {
    $doctor = Doctor::factory()->create();
    DoctorSchedule::factory()->for($doctor)->create(['day_of_week' => Carbon::MONDAY, 'starts_at' => '08:00', 'ends_at' => '12:00']);
    $admin = User::factory()->admin()->create();
    $nextMonday = now()->next(Carbon::MONDAY);

    $payload = fn (string $time) => [
        'patient_id' => Patient::factory()->create()->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => $nextMonday->format('Y-m-d').'T'.$time,
        'reason' => 'Control',
    ];

    $this->actingAs($admin)
        ->post(route('appointments.store'), $payload('15:00'))
        ->assertSessionHasErrors('scheduled_at');

    $this->actingAs($admin)
        ->post(route('appointments.store'), $payload('09:30'))
        ->assertSessionHasNoErrors();
});
