<?php

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lets the doctor see their own professional profile in settings', function () {
    $doctor = Doctor::factory()->create(['consultation_fee' => 55, 'slot_minutes' => 20]);
    DoctorSchedule::factory()->for($doctor)->create(['day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '12:00']);

    $this->actingAs($doctor->user)
        ->get(route('doctor-profile.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/DoctorProfile')
            ->where('profile.id', $doctor->id)
            ->where('profile.license_number', $doctor->license_number)
            ->where('profile.slot_minutes', 20)
            ->where('profile.schedule_summary.0.days', 'Lun')
        );
});

it('shows the professional profile only to doctors', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('doctor-profile.show'))->assertForbidden();
    $this->actingAs(User::factory()->receptionist()->create())->get(route('doctor-profile.show'))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('doctor-profile.show'))->assertForbidden();
});

it('requires authentication for the professional profile', function () {
    $this->get(route('doctor-profile.show'))->assertRedirect(route('login'));
});
