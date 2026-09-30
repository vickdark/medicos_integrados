<?php

use App\Models\Appointment;
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

it('creates the same block for several days at once', function () {
    $doctor = Doctor::factory()->create();

    $this->actingAs($doctor->user)
        ->post(route('schedules.store', $doctor), [
            'days' => [1, 2, 3],
            'starts_at' => '08:00',
            'ends_at' => '12:00',
        ])
        ->assertSessionHasNoErrors();

    expect($doctor->schedules()->pluck('day_of_week')->sort()->values()->all())->toBe([1, 2, 3]);
});

it('names the days that overlap when adding several days', function () {
    $doctor = Doctor::factory()->create();
    DoctorSchedule::factory()->for($doctor)->create(['day_of_week' => 2, 'starts_at' => '08:00', 'ends_at' => '12:00']);

    $this->actingAs($doctor->user)
        ->post(route('schedules.store', $doctor), ['days' => [1, 2], 'starts_at' => '09:00', 'ends_at' => '11:00'])
        ->assertSessionHasErrors('starts_at');

    expect($doctor->schedules()->count())->toBe(1);
});

it('lets the doctor choose how long each appointment lasts', function () {
    $doctor = Doctor::factory()->create();

    $this->actingAs($doctor->user)
        ->patch(route('schedules.slot', $doctor), ['slot_minutes' => 20])
        ->assertSessionHasNoErrors();

    expect($doctor->fresh()->slot_minutes)->toBe(20);

    $this->actingAs($doctor->user)
        ->patch(route('schedules.slot', $doctor), ['slot_minutes' => 7])
        ->assertSessionHasErrors('slot_minutes');

    $this->actingAs(Doctor::factory()->create()->user)
        ->patch(route('schedules.slot', $doctor), ['slot_minutes' => 15])
        ->assertForbidden();
});

it('exposes office hours and taken times to build the booking calendar', function () {
    $doctor = Doctor::factory()->create(['slot_minutes' => 30]);
    $monday = now()->next('Monday')->startOfDay();
    DoctorSchedule::factory()->for($doctor)->create(['day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '12:00']);
    $taken = Appointment::factory()->confirmed()->create(['doctor_id' => $doctor->id, 'scheduled_at' => $monday->copy()->setTime(9, 0)]);
    Appointment::factory()->cancelled()->create(['doctor_id' => $doctor->id, 'scheduled_at' => $monday->copy()->setTime(10, 0)]);
    $receptionist = User::factory()->receptionist()->create();
    $query = ['from' => $monday->toDateString(), 'to' => $monday->copy()->addDay()->toDateString()];

    $this->actingAs($receptionist)
        ->getJson(route('doctors.availability', ['doctor' => $doctor] + $query))
        ->assertOk()
        ->assertJsonPath('slot_minutes', 30)
        ->assertJsonPath('has_schedule', true)
        ->assertJsonPath('days.'.$monday->toDateString().'.blocks.0.start', '08:00')
        ->assertJsonPath('days.'.$monday->toDateString().'.booked', ['09:00'])
        ->assertJsonPath('days.'.$monday->copy()->addDay()->toDateString().'.blocks', []);

    $this->actingAs($receptionist)
        ->getJson(route('doctors.availability', ['doctor' => $doctor, 'exclude' => $taken->id] + $query))
        ->assertJsonPath('days.'.$monday->toDateString().'.booked', []);
});

it('treats a doctor without office hours as open all day in the availability feed', function () {
    $doctor = Doctor::factory()->create();
    $day = now()->addDay()->toDateString();

    $this->actingAs(User::factory()->receptionist()->create())
        ->getJson(route('doctors.availability', ['doctor' => $doctor, 'from' => $day, 'to' => $day]))
        ->assertJsonPath('has_schedule', false)
        ->assertJsonPath('days.'.$day.'.blocks.0.start', '07:00');
});

it('rejects appointments that overlap another one of the same doctor', function () {
    $doctor = Doctor::factory()->create(['slot_minutes' => 30]);
    $day = now()->addDays(3)->startOfDay();
    Appointment::factory()->confirmed()->create(['doctor_id' => $doctor->id, 'scheduled_at' => $day->copy()->setTime(10, 0)]);
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();
    $payload = fn (int $hour, int $minute) => [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => $day->copy()->setTime($hour, $minute)->format('Y-m-d\TH:i'),
        'reason' => 'Control',
    ];

    $this->actingAs($receptionist)->post(route('appointments.store'), $payload(10, 15))->assertSessionHasErrors('scheduled_at');
    $this->actingAs($receptionist)->post(route('appointments.store'), $payload(9, 45))->assertSessionHasErrors('scheduled_at');
    $this->actingAs($receptionist)->post(route('appointments.store'), $payload(10, 30))->assertSessionHasNoErrors();
});

it('groups the weekly office hours for display', function () {
    $doctor = Doctor::factory()->create();

    foreach ([1, 2, 3] as $day) {
        DoctorSchedule::factory()->for($doctor)->create(['day_of_week' => $day, 'starts_at' => '08:00', 'ends_at' => '12:00']);
    }

    DoctorSchedule::factory()->for($doctor)->create(['day_of_week' => 5, 'starts_at' => '14:00', 'ends_at' => '18:00']);
    DoctorSchedule::factory()->for($doctor)->create(['day_of_week' => 5, 'starts_at' => '08:00', 'ends_at' => '10:00']);

    expect($doctor->fresh()->weeklyScheduleSummary())->toBe([
        ['days' => 'Lun – Mié', 'ranges' => ['8:00 AM – 12:00 PM']],
        ['days' => 'Vie', 'ranges' => ['8:00 AM – 10:00 AM', '2:00 PM – 6:00 PM']],
    ]);
});
