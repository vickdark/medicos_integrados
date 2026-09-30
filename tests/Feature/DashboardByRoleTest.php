<?php

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
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

it('gives the administrator figures, charts and work queues', function () {
    $doctor = Doctor::factory()->create();
    Patient::factory()->count(3)->create();
    Appointment::factory()->create(['doctor_id' => $doctor->id, 'status' => 'requested', 'scheduled_at' => now()->addDays(2)]);
    Appointment::factory()->confirmed()->create(['doctor_id' => $doctor->id, 'scheduled_at' => now()->setTime(23, 0)]);
    Appointment::factory()->cancelled()->create(['doctor_id' => $doctor->id, 'scheduled_at' => now()->setTime(22, 0)]);
    Payment::factory()->create(['amount' => 100, 'paid_at' => now()->toDateString()]);
    Payment::factory()->pending()->create(['amount' => 40]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.kpis.requests', 1)
            ->where('insights.kpis.appointments_today', fn ($value) => $value >= 1)
            ->where('insights.kpis.income_month', 100)
            ->where('insights.kpis.pending_amount', 40)
            ->where('insights.kpis.pending_count', 1)
            ->has('insights.income_by_month', 6)
            ->where('insights.income_by_month.5.current', true)
            ->where('insights.income_by_month.5.value', 100)
            ->has('insights.appointments_by_day', 14)
            ->has('insights.pending_requests', 1)
            ->has('insights.pending_payments', 1)
            ->has('insights.top_doctors', 1)
        );
});

it('does not show reception the ranking of doctors', function () {
    Appointment::factory()->confirmed()->create(['scheduled_at' => now()]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('insights.kpis')->has('insights.top_doctors', 0));
});

it('gives the doctor only their own figures and agenda', function () {
    $doctor = Doctor::factory()->create();
    Appointment::factory()->confirmed()->create(['doctor_id' => $doctor->id, 'scheduled_at' => now()->addDay()->setTime(9, 0)]);
    Appointment::factory()->confirmed()->create(['scheduled_at' => now()->addDay()->setTime(10, 0)]);
    Consultation::factory()->count(2)->create(['doctor_id' => $doctor->id, 'consulted_at' => now()]);
    Consultation::factory()->create(['consulted_at' => now()]);

    $this->actingAs($doctor->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.kpis.consultations_month', 2)
            ->where('insights.next_appointment.doctor.id', $doctor->id)
            ->has('insights.week_load', 7)
            ->has('insights.consultations_by_month', 6)
            ->where('insights.consultations_by_month.5.value', 2)
            ->has('insights.recent_consultations', 2)
        );
});

it('does not give the patient the staff insights', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('insights', null));
});
