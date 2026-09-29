<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentRequested;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('notifies the clinic staff when a patient requests an appointment', function () {
    $admin = User::factory()->admin()->create();
    $receptionist = User::factory()->receptionist()->create();
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)->post(route('appointments.store'), [
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDays(3)->setTime(10, 0)->format('Y-m-d\TH:i'),
        'reason' => 'Control',
    ]);

    Notification::assertSentTo([$admin, $receptionist], AppointmentRequested::class);
    Notification::assertNotSentTo($doctor->user, AppointmentRequested::class);
});

it('notifies the patient when the clinic confirms or schedules an appointment', function () {
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->for($patient)->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('appointments.status', $appointment), ['status' => 'confirmed']);

    Notification::assertSentTo($patient->user, AppointmentConfirmed::class);
});

it('notifies the patient when the clinic cancels', function () {
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->for($patient)->confirmed()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('appointments.status', $appointment), ['status' => 'cancelled']);

    Notification::assertSentTo($patient->user, AppointmentCancelled::class);
});

it('notifies the doctor when the patient cancels', function () {
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->for($patient)->confirmed()->create();

    $this->actingAs($patient->user)
        ->patch(route('appointments.status', $appointment), ['status' => 'cancelled']);

    Notification::assertSentTo($appointment->doctor->user, AppointmentCancelled::class);
    Notification::assertNotSentTo($patient->user, AppointmentCancelled::class);
});

it('skips notifications for patients without a portal account', function () {
    $appointment = Appointment::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('appointments.status', $appointment), ['status' => 'confirmed'])
        ->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

it('renders the confirmation email in Spanish', function () {
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->for($patient)->confirmed()->create();

    $mail = (new AppointmentConfirmed($appointment))->toMail($patient->user);

    expect($mail->subject)->toBe('Tu cita ha sido confirmada')
        ->and(implode(' ', $mail->introLines))->toContain($appointment->doctor->user->name);
});
