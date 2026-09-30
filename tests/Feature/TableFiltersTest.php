<?php

use App\Enums\AuditAction;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Specialty;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('searches appointments by patient name and filters by date range', function () {
    $patient = Patient::factory()->create(['first_name' => 'Zoila', 'last_name' => 'Vargas']);
    Appointment::factory()->for($patient)->create(['scheduled_at' => now()->addDays(2)->setTime(10, 0)]);
    Appointment::factory()->for($patient)->create(['scheduled_at' => now()->addDays(20)->setTime(10, 0)]);
    Appointment::factory()->count(2)->create();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('appointments.index', ['search' => 'Vargas']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('appointments.data', 2)
            ->where('filters.search', 'Vargas')
        );

    $this->actingAs($admin)
        ->get(route('appointments.index', [
            'search' => 'Vargas',
            'from' => now()->addDay()->toDateString(),
            'to' => now()->addDays(5)->toDateString(),
        ]))
        ->assertInertia(fn (Assert $page) => $page->has('appointments.data', 1));
});

it('rejects an inverted date range', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('payments.index', ['from' => '2026-05-10', 'to' => '2026-05-01']))
        ->assertSessionHasErrors('to');
});

it('filters payments by status and recalculates the totals', function () {
    Payment::factory()->count(2)->create(['amount' => 50]);
    Payment::factory()->pending()->create(['amount' => 30, 'concept' => 'Laboratorio']);

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('payments.index', ['status' => 'pending']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 1)
            ->where('totals.pending', 30)
            ->where('totals.paid', 0)
        );

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('payments.index', ['search' => 'Laboratorio']))
        ->assertInertia(fn (Assert $page) => $page->has('payments.data', 1));
});

it('searches doctors by name or specialty', function () {
    $pulmonology = Specialty::factory()->create(['name' => 'Neumología']);
    Doctor::factory()->for($pulmonology)->create();
    Doctor::factory()->for(User::factory()->doctor()->state(['name' => 'Dr. Ernesto Salas']))->create();
    Doctor::factory()->create();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('doctors.index', ['search' => 'Neumo']))
        ->assertInertia(fn (Assert $page) => $page->has('doctors.data', 1));

    $this->actingAs($admin)
        ->get(route('doctors.index', ['search' => 'Ernesto']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('doctors.data', 1)
            ->where('doctors.data.0.name', 'Dr. Ernesto Salas')
        );
});

it('paginates and searches specialties', function () {
    Specialty::factory()->count(20)->create();
    Specialty::factory()->create(['name' => 'Otorrinolaringología']);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('specialties.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('specialties.data', 10)
            ->where('specialties.total', 21)
        );

    $this->actingAs($admin)
        ->get(route('specialties.index', ['search' => 'Otorrino']))
        ->assertInertia(fn (Assert $page) => $page->has('specialties.data', 1));
});

it('searches and filters the audit trail', function () {
    AuditLog::factory()->create(['description' => 'Consultó la historia clínica']);
    AuditLog::factory()->create(['action' => AuditAction::Downloaded, 'description' => 'Descargó el archivo «rayos-x.png»']);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('audit-logs.index', ['search' => 'rayos']))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1));

    $this->actingAs($admin)
        ->get(route('audit-logs.index', ['action' => 'viewed']))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1));
});
