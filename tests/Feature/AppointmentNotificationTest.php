<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentRequested;
use App\Notifications\AppointmentRescheduled;
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

it('lets the clinic reprogram an appointment keeping its status and notifies the patient', function () {
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->for($patient)->confirmed()->create(['scheduled_at' => now()->addDays(3)->setTime(10, 0)]);
    $newDate = now()->addDays(5)->setTime(11, 0);

    $this->actingAs(User::factory()->receptionist()->create())
        ->put(route('appointments.update', $appointment), ['scheduled_at' => $newDate->format('Y-m-d\TH:i')])
        ->assertRedirect(route('appointments.index'))
        ->assertSessionHasNoErrors();

    $appointment->refresh();

    expect($appointment->scheduled_at->equalTo($newDate))->toBeTrue()
        ->and($appointment->status->value)->toBe('confirmed');

    Notification::assertSentTo($patient->user, AppointmentRescheduled::class);
});

it('sends a patient reprogramming back to confirmation and notifies the clinic', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->for($patient)->confirmed()->create(['scheduled_at' => now()->addDays(3)->setTime(10, 0)]);

    $this->actingAs($patient->user)
        ->put(route('appointments.update', $appointment), ['scheduled_at' => now()->addDays(6)->setTime(9, 0)->format('Y-m-d\TH:i')])
        ->assertSessionHasNoErrors();

    expect($appointment->fresh()->status->value)->toBe('requested');

    Notification::assertSentTo([$admin, $appointment->doctor->user], AppointmentRescheduled::class);
    Notification::assertNotSentTo($patient->user, AppointmentRescheduled::class);
});

it('validates the new date when reprogramming', function () {
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->confirmed()->create(['doctor_id' => $doctor->id, 'scheduled_at' => now()->addDays(3)->setTime(10, 0)]);
    Appointment::factory()->confirmed()->create(['doctor_id' => $doctor->id, 'scheduled_at' => now()->addDays(4)->setTime(10, 0)]);
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)
        ->put(route('appointments.update', $appointment), ['scheduled_at' => now()->subDay()->format('Y-m-d\TH:i')])
        ->assertSessionHasErrors('scheduled_at');

    $this->actingAs($receptionist)
        ->put(route('appointments.update', $appointment), ['scheduled_at' => now()->addDays(4)->setTime(10, 0)->format('Y-m-d\TH:i')])
        ->assertSessionHasErrors('scheduled_at');

    $this->actingAs($receptionist)
        ->put(route('appointments.update', $appointment), ['scheduled_at' => now()->addDays(3)->setTime(10, 0)->format('Y-m-d\TH:i')])
        ->assertSessionHasErrors('scheduled_at');
});

it('does not allow reprogramming cancelled appointments or other people\'s appointments', function () {
    $cancelled = Appointment::factory()->cancelled()->create();
    $foreign = Appointment::factory()->confirmed()->create();
    $patient = Patient::factory()->withAccount()->create();
    $date = ['scheduled_at' => now()->addDays(8)->setTime(10, 0)->format('Y-m-d\TH:i')];

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('appointments.update', $cancelled), $date)->assertForbidden();
    $this->actingAs($patient->user)
        ->put(route('appointments.update', $foreign), $date)->assertForbidden();
    $this->actingAs($patient->user)
        ->get(route('appointments.edit', $foreign))->assertForbidden();
});

it('shows the reprogramming form and offers the action in the calendar feed', function () {
    $appointment = Appointment::factory()->confirmed()->create();
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)
        ->get(route('appointments.edit', $appointment))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('appointments/Reschedule')->where('appointment.id', $appointment->id));

    $this->actingAs($receptionist)
        ->getJson(route('appointments.calendar', [
            'from' => $appointment->scheduled_at->copy()->subDay()->toDateString(),
            'to' => $appointment->scheduled_at->copy()->addDay()->toDateString(),
        ]))
        ->assertJsonPath('data.0.can.reschedule', true);
});
