<?php

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('reminds the patient of a confirmed appointment happening within a day', function () {
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->confirmed()->for($patient)->create(['scheduled_at' => now()->addHours(20)]);

    $this->artisan('appointments:send-reminders')->assertSuccessful();

    Notification::assertSentTo($patient->user, AppointmentReminder::class);
    expect($appointment->fresh()->reminder_sent_at)->not->toBeNull();
});

it('does not remind the same appointment twice', function () {
    Appointment::factory()->confirmed()->for(Patient::factory()->withAccount()->create())->create(['scheduled_at' => now()->addHours(20)]);

    $this->artisan('appointments:send-reminders');
    $this->artisan('appointments:send-reminders');

    Notification::assertCount(1);
});

it('uses the email of the patient record when there is no account', function () {
    $patient = Patient::factory()->create(['email' => 'sin-cuenta@example.com']);
    Appointment::factory()->confirmed()->for($patient)->create(['scheduled_at' => now()->addHours(20)]);

    $this->artisan('appointments:send-reminders');

    Notification::assertSentOnDemand(AppointmentReminder::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'sin-cuenta@example.com');
});

it('skips appointments that are far away, too close, past or not confirmed', function () {
    $patient = Patient::factory()->withAccount()->create();

    Appointment::factory()->confirmed()->for($patient)->create(['scheduled_at' => now()->addDays(3)]);
    Appointment::factory()->confirmed()->for($patient)->create(['scheduled_at' => now()->addMinutes(30)]);
    Appointment::factory()->confirmed()->for($patient)->create(['scheduled_at' => now()->subHours(3)]);
    Appointment::factory()->for($patient)->create(['status' => 'requested', 'scheduled_at' => now()->addHours(20)]);
    Appointment::factory()->for($patient)->create(['status' => 'cancelled', 'scheduled_at' => now()->addHours(20)]);

    $this->artisan('appointments:send-reminders');

    Notification::assertNothingSent();
});

it('sends a new reminder after the appointment is rescheduled', function () {
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->confirmed()->for($patient)->create([
        'scheduled_at' => now()->addHours(20),
        'reminder_sent_at' => now()->subHour(),
    ]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->put(route('appointments.update', $appointment), [
            'scheduled_at' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d\TH:i'),
        ]);

    expect($appointment->fresh()->reminder_sent_at)->toBeNull();
});
