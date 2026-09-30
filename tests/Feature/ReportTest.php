<?php

use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Specialty;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lets staff and doctors see the reports but not patients', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('reports.index'))->assertOk();
    $this->actingAs(User::factory()->receptionist()->create())->get(route('reports.index'))->assertOk();
    $this->actingAs(Doctor::factory()->create()->user)->get(route('reports.index'))->assertOk();
    $this->actingAs(User::factory()->patient()->create())->get(route('reports.index'))->assertForbidden();
    $this->actingAs(User::factory()->patient()->create())->get(route('reports.export', ['format' => 'xlsx']))->assertForbidden();
});

it('limits a doctor to their own figures without income', function () {
    $doctor = Doctor::factory()->create();
    $other = Doctor::factory()->create();
    $date = now()->startOfMonth()->addHours(9);

    Appointment::factory()->confirmed()->for($doctor)->create(['scheduled_at' => $date]);
    Appointment::factory()->confirmed()->for($other)->create(['scheduled_at' => $date]);
    Appointment::factory()->confirmed()->for($other)->create(['scheduled_at' => $date]);

    $this->actingAs($doctor->user)
        ->get(route('reports.index', [
            'doctor_id' => $other->id,
            'group' => 'specialty',
            'to' => now()->endOfMonth()->toDateString(),
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('restricted', true)
            ->where('filters.doctor_id', $doctor->id)
            ->where('filters.group', 'doctor')
            ->has('report.rows', 1)
            ->where('report.rows.0.0', $doctor->user->name)
            ->where('report.rows.0.2', 1)
            ->where('types', [['value' => 'appointments', 'label' => 'Citas'], ['value' => 'consultations', 'label' => 'Consultas']])
            ->where('doctors', [])
            ->where('specialties', [])
        );

    $this->actingAs($doctor->user)->get(route('reports.index', ['type' => 'income']))->assertSessionHasErrors('type');
    $this->actingAs($doctor->user)
        ->get(route('reports.export', ['format' => 'xlsx', 'type' => 'income']))
        ->assertSessionHasErrors('type');
    $this->actingAs($doctor->user)
        ->get(route('reports.export', ['format' => 'xlsx', 'doctor_id' => $other->id]))
        ->assertOk()
        ->assertDownload();
});

it('keeps doctors without a profile out of the reports', function () {
    $this->actingAs(User::factory()->doctor()->create())->get(route('reports.index'))->assertForbidden();
});

it('counts appointments by doctor inside the period only', function () {
    $doctor = Doctor::factory()->create();
    $other = Doctor::factory()->create();
    $inRange = now()->startOfMonth()->addHours(10);

    Appointment::factory()->confirmed()->for($doctor)->create(['scheduled_at' => $inRange]);
    Appointment::factory()->for($doctor)->create(['status' => 'cancelled', 'scheduled_at' => $inRange]);
    Appointment::factory()->confirmed()->for($other)->create(['scheduled_at' => $inRange]);
    Appointment::factory()->confirmed()->for($doctor)->create(['scheduled_at' => now()->subMonths(3)]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('reports.index', [
            'type' => 'appointments',
            'group' => 'doctor',
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->endOfMonth()->toDateString(),
            'doctor_id' => $doctor->id,
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.headings', ['Médico', 'Especialidad', 'Citas', 'Solicitadas', 'Confirmadas', 'Completadas', 'Canceladas'])
            ->has('report.rows', 1)
            ->where('report.rows.0', [$doctor->user->name, $doctor->specialty->name, 2, 0, 1, 0, 1])
            ->where('report.totals', ['Total', null, 2, 0, 1, 0, 1])
        );
});

it('groups by specialty and filters by specialty', function () {
    $cardiology = Specialty::factory()->create(['name' => 'Cardiología']);
    $pediatrics = Specialty::factory()->create(['name' => 'Pediatría']);
    $first = Doctor::factory()->create(['specialty_id' => $cardiology->id]);
    $second = Doctor::factory()->create(['specialty_id' => $cardiology->id]);
    $third = Doctor::factory()->create(['specialty_id' => $pediatrics->id]);
    $date = now()->startOfMonth()->addHours(9);

    foreach ([$first, $second, $third] as $doctor) {
        Appointment::factory()->confirmed()->for($doctor)->create(['scheduled_at' => $date]);
    }

    $admin = User::factory()->admin()->create();
    $query = ['type' => 'appointments', 'group' => 'specialty', 'to' => now()->endOfMonth()->toDateString()];

    $this->actingAs($admin)->get(route('reports.index', $query))
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.rows.0', ['Cardiología', 2, 0, 2, 0, 0])
            ->where('report.rows.1', ['Pediatría', 1, 0, 1, 0, 0])
        );

    $this->actingAs($admin)->get(route('reports.index', [...$query, 'specialty_id' => $pediatrics->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('report.rows', 1)
            ->where('report.rows.0.0', 'Pediatría')
        );
});

it('reports paid and pending income and keeps payments without appointment apart', function () {
    $appointment = Appointment::factory()->confirmed()->create();
    $today = now()->toDateString();

    Payment::factory()->for($appointment->patient)->create(['appointment_id' => $appointment->id, 'amount' => 100, 'paid_at' => $today]);
    Payment::factory()->for($appointment->patient)->create(['appointment_id' => $appointment->id, 'amount' => 50, 'paid_at' => $today]);
    Payment::factory()->pending()->for($appointment->patient)->create(['appointment_id' => $appointment->id, 'amount' => 30]);
    Payment::factory()->create(['appointment_id' => null, 'amount' => 20, 'paid_at' => $today]);
    Payment::factory()->create(['appointment_id' => $appointment->id, 'amount' => 999, 'status' => PaymentStatus::Voided, 'paid_at' => $today]);
    Payment::factory()->create(['appointment_id' => $appointment->id, 'amount' => 500, 'paid_at' => now()->subMonths(2)->toDateString()]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('reports.index', ['type' => 'income', 'group' => 'doctor', 'to' => $today]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.headings.2', 'Pagos cobrados')
            ->where('report.money_columns', [3, 5])
            ->where('report.rows.0', [$appointment->doctor->user->name, $appointment->doctor->specialty->name, 2, 150, 1, 30])
            ->where('report.rows.1', ['Sin médico (pago sin cita)', null, 1, 20, 0, 0])
            ->where('report.totals', ['Total', null, 3, 170, 1, 30])
        );
});

it('counts consultations and distinct patients by doctor', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();

    Consultation::factory()->count(2)->create(['doctor_id' => $doctor->id, 'patient_id' => $patient->id, 'consulted_at' => now()]);
    Consultation::factory()->create(['doctor_id' => $doctor->id, 'consulted_at' => now()]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('reports.index', ['type' => 'consultations', 'to' => now()->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.rows.0', [$doctor->user->name, $doctor->specialty->name, 3, 2])
        );
});

it('validates the period and the filters', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('reports.index', ['from' => '2026-05-10', 'to' => '2026-05-01', 'doctor_id' => 99999, 'type' => 'nope']))
        ->assertSessionHasErrors(['to', 'doctor_id', 'type']);
});

it('exports the report to Excel and PDF with its totals', function () {
    $doctor = Doctor::factory()->create();
    Appointment::factory()->confirmed()->for($doctor)->create(['scheduled_at' => now()->startOfMonth()->addHours(9)]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('reports.export', ['format' => 'xlsx', 'type' => 'appointments', 'to' => now()->endOfMonth()->toDateString()]))
        ->assertOk()
        ->assertDownload();

    $this->actingAs($admin)
        ->get(route('reports.export', ['format' => 'pdf', 'type' => 'income']))
        ->assertOk()
        ->assertDownload();

    $this->actingAs($admin)->get(route('reports.export'))->assertSessionHasErrors('format');
});
