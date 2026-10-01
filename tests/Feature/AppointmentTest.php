<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
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

it('returns only the visible appointments inside the calendar window', function () {
    $doctor = Doctor::factory()->create();
    $inside = Appointment::factory()->confirmed()->create(['doctor_id' => $doctor->id, 'scheduled_at' => '2026-10-10 09:00:00']);
    Appointment::factory()->confirmed()->create(['doctor_id' => $doctor->id, 'scheduled_at' => '2026-12-10 09:00:00']);
    Appointment::factory()->confirmed()->create(['scheduled_at' => '2026-10-10 10:00:00']);

    $this->actingAs($doctor->user)
        ->getJson(route('appointments.calendar', ['from' => '2026-09-28', 'to' => '2026-11-08']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inside->id);

    $this->actingAs(User::factory()->receptionist()->create())
        ->getJson(route('appointments.calendar', ['from' => '2026-09-28', 'to' => '2026-11-08']))
        ->assertJsonCount(2, 'data');
});

it('validates the calendar window', function () {
    $this->actingAs(User::factory()->receptionist()->create())
        ->getJson(route('appointments.calendar', ['from' => '2026-10-10', 'to' => '2026-10-01']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to');

    $this->getJson(route('appointments.calendar'))->assertUnprocessable();
});

it('requires authentication for the calendar feed', function () {
    auth()->logout();

    $this->getJson(route('appointments.calendar', ['from' => '2026-10-01', 'to' => '2026-10-31']))->assertUnauthorized();
});

it('offers the payment action only to staff on unpaid confirmed or completed appointments', function () {
    $appointment = Appointment::factory()->confirmed()->create();
    $calendar = fn () => route('appointments.calendar', [
        'from' => $appointment->scheduled_at->copy()->subDay()->toDateString(),
        'to' => $appointment->scheduled_at->copy()->addDay()->toDateString(),
    ]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->getJson($calendar())
        ->assertJsonPath('data.0.can.register_payment', true);

    Payment::factory()->create(['appointment_id' => $appointment->id, 'patient_id' => $appointment->patient_id]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->getJson($calendar())
        ->assertJsonPath('data.0.can.register_payment', false);

    $this->actingAs($appointment->doctor->user)
        ->getJson($calendar())
        ->assertJsonPath('data.0.can.register_payment', false);
});

it('shows the payment indicator of the appointment and updates it when the payment is settled', function () {
    $appointment = Appointment::factory()->confirmed()->create();
    $receptionist = User::factory()->receptionist()->create();
    $feed = route('appointments.calendar', [
        'from' => $appointment->scheduled_at->copy()->subDay()->toDateString(),
        'to' => $appointment->scheduled_at->copy()->addDay()->toDateString(),
    ]);

    $this->actingAs($receptionist)->getJson($feed)->assertJsonPath('data.0.payment_status', null);

    $payment = Payment::factory()->pending()->create(['appointment_id' => $appointment->id, 'patient_id' => $appointment->patient_id]);

    $this->actingAs($receptionist)->getJson($feed)
        ->assertJsonPath('data.0.payment_status.value', 'pending')
        ->assertJsonPath('data.0.can.register_payment', false);

    $this->actingAs($receptionist)
        ->patch(route('payments.paid', $payment), ['method' => 'cash', 'paid_at' => now()->toDateString()]);

    $this->actingAs($receptionist)->getJson($feed)
        ->assertJsonPath('data.0.payment_status.value', 'paid')
        ->assertJsonPath('data.0.payment_status.label', 'Pagada');
});

it('lets staff book a slot later today in the clinic time zone but not one that already passed', function () {
    $this->travelTo(now()->setTimezone('America/Bogota')->setDate(2026, 10, 7)->setTime(10, 0));

    $doctor = Doctor::factory()->create();
    $receptionist = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();
    $book = fn (string $time) => $this->actingAs($receptionist)->post(route('appointments.store'), [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => "2026-10-07T{$time}",
        'reason' => 'Control',
    ]);

    $book('09:00')->assertSessionHasErrors('scheduled_at');
    $book('10:00')->assertSessionHasErrors('scheduled_at');
    $book('14:00')->assertSessionHasNoErrors();

    expect(config('app.timezone'))->toBe('America/Bogota')
        ->and(Appointment::query()->sole()->scheduled_at->format('H:i'))->toBe('14:00');
});

it('does not send the doctor fee to the patient when booking or rescheduling', function () {
    $doctor = Doctor::factory()->create(['consultation_fee' => 85000]);
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->for($patient)->for($doctor)->create(['status' => AppointmentStatus::Requested]);

    $this->actingAs($patient->user)
        ->get(route('appointments.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('appointments/Create')
            ->where('doctors.0.consultation_fee', null));

    $this->actingAs($patient->user)
        ->get(route('appointments.edit', $appointment))
        ->assertInertia(fn (Assert $page) => $page->where('doctors.0.consultation_fee', null));

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('appointments.create'))
        ->assertInertia(fn (Assert $page) => $page->where('doctors.0.consultation_fee', '85000.00'));
});
