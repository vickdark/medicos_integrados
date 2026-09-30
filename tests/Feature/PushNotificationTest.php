<?php

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use NotificationChannels\WebPush\WebPushChannel;

function subscriptionPayload(array $overrides = []): array
{
    return [
        'endpoint' => 'https://push.example.com/send/abc123',
        'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
        'contentEncoding' => 'aes128gcm',
        ...$overrides,
    ];
}

it('saves the browser subscription of the signed-in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('push-subscriptions.store'), subscriptionPayload())
        ->assertNoContent();

    expect($user->pushSubscriptions)->toHaveCount(1)
        ->and($user->pushSubscriptions->first()->endpoint)->toBe('https://push.example.com/send/abc123');
});

it('does not duplicate the subscription of the same device', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('push-subscriptions.store'), subscriptionPayload());
    $this->actingAs($user)->postJson(route('push-subscriptions.store'), subscriptionPayload(['keys' => ['p256dh' => 'new-key', 'auth' => 'new-token']]));

    expect($user->pushSubscriptions()->count())->toBe(1)
        ->and($user->pushSubscriptions()->first()->public_key)->toBe('new-key');
});

it('removes the subscription when the user turns notifications off', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson(route('push-subscriptions.store'), subscriptionPayload());

    $this->actingAs($user)
        ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => 'https://push.example.com/send/abc123'])
        ->assertNoContent();

    expect($user->pushSubscriptions()->count())->toBe(0);
});

it('rejects invalid subscriptions and guests', function () {
    $this->postJson(route('push-subscriptions.store'), subscriptionPayload())->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->postJson(route('push-subscriptions.store'), subscriptionPayload(['endpoint' => 'http://insecure.example.com']))
        ->assertJsonValidationErrors('endpoint');

    $this->actingAs(User::factory()->create())
        ->postJson(route('push-subscriptions.store'), ['endpoint' => 'https://push.example.com/x'])
        ->assertJsonValidationErrors(['keys.p256dh', 'keys.auth']);
});

it('adds the push channel to the reminder only for users with a subscribed device', function () {
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->confirmed()->for($patient)->create();
    $notification = new AppointmentReminder($appointment);

    expect($notification->via($patient->user))->toBe(['mail']);

    $patient->user->updatePushSubscription('https://push.example.com/send/abc123', 'key', 'token');

    expect($notification->via($patient->user->fresh()))->toBe(['mail', WebPushChannel::class]);
});

it('builds the push message of the reminder with the appointment details', function () {
    $appointment = Appointment::factory()->confirmed()->create(['scheduled_at' => now()->addHours(20)]);
    $notification = new AppointmentReminder($appointment);

    $message = $notification->toWebPush($appointment->patient, $notification)->toArray();

    expect($message['title'])->toBe('Recordatorio de tu cita médica')
        ->and($message['body'])->toContain($appointment->doctor->user->name)
        ->and($message['data']['url'])->toBe(route('appointments.index', absolute: false));
});
