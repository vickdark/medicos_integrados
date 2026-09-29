<?php

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shares the role of the authenticated user with the frontend', function (string $factoryState, UserRole $role) {
    $user = User::factory()->{$factoryState}()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.role.value', $role->value)
            ->has('stats')
        );
})->with([
    'admin' => ['admin', UserRole::Admin],
    'doctor' => ['doctor', UserRole::Doctor],
    'receptionist' => ['receptionist', UserRole::Receptionist],
]);

it('shows the patient only their own upcoming appointments', function () {
    $patient = Patient::factory()->withAccount()->create();
    Appointment::factory()->for($patient)->create();
    Appointment::factory()->count(2)->create();

    $this->actingAs($patient->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.role.value', UserRole::Patient->value)
            ->where('auth.patientId', $patient->id)
            ->has('upcomingAppointments', 1)
        );
});

it('shows the doctor only the appointments of their agenda', function () {
    $doctor = Doctor::factory()->create();
    Appointment::factory()->count(2)->for($doctor)->create();
    Appointment::factory()->create();

    $this->actingAs($doctor->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('upcomingAppointments', 2));
});
