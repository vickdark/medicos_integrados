<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;

it('registers new users as patients with their own record', function () {
    $this->post(route('register.store'), [
        'name' => 'Lucía Fernández Soto',
        'email' => 'lucia@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'privacy' => 'on',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'lucia@example.com')->sole();

    expect($user->role)->toBe(UserRole::Patient)
        ->and($user->patient->first_name)->toBe('Lucía')
        ->and($user->patient->last_name)->toBe('Fernández Soto');
});

it('links the account to the record created by the clinic with the same email', function () {
    $existingPatient = Patient::factory()->create(['email' => 'pedro@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Pedro',
        'email' => 'pedro@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'privacy' => 'on',
    ]);

    expect(Patient::query()->count())->toBe(1)
        ->and($existingPatient->refresh()->user->email)->toBe('pedro@example.com');
});

it('records the acceptance of the privacy policy with its date and version', function () {
    $this->post(route('register.store'), [
        'name' => 'Marta Ruiz',
        'email' => 'marta@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'privacy' => 'on',
    ]);

    $user = User::query()->where('email', 'marta@example.com')->sole();

    expect($user->privacy_policy_version)->toBe(config('privacy.version'))
        ->and($user->privacy_accepted_at)->not->toBeNull();
});

it('requires accepting the privacy policy to register', function () {
    $this->post(route('register.store'), [
        'name' => 'Marta Ruiz',
        'email' => 'marta@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('privacy');

    expect(User::query()->where('email', 'marta@example.com')->exists())->toBeFalse();
});

it('shows the privacy policy publicly', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Privacy')
            ->where('version', config('privacy.version'))
        );
});
