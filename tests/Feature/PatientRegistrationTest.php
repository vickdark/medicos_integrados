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
    ]);

    expect(Patient::query()->count())->toBe(1)
        ->and($existingPatient->refresh()->user->email)->toBe('pedro@example.com');
});
