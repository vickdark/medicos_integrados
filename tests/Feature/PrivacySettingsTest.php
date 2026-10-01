<?php

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('uses the defaults of the config until the administrator saves something', function () {
    $policy = AppSetting::privacyPolicy();

    expect($policy['version'])->toBe(config('privacy.version'))
        ->and($policy['company'])->toBe(config('privacy.company'))
        ->and($policy['nit'])->toBeNull();
});

it('lets the administrator publish the data of the controller in the policy', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('privacy-settings.update'), [
            'company' => 'Clínica Salud SAS',
            'nit' => '900.123.456-7',
            'address' => 'Calle 1 # 2-3',
            'phone' => '3001234567',
            'contact_email' => 'datos@clinica.test',
            'rnbd_registration' => 'RNBD-123',
        ])
        ->assertSessionHasNoErrors();

    $this->get(route('privacy'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Privacy')
            ->where('controller.company', 'Clínica Salud SAS')
            ->where('controller.nit', '900.123.456-7')
            ->where('controller.email', 'datos@clinica.test')
            ->where('controller.rnbd', 'RNBD-123'));

    expect(AuditLog::query()->where('description', 'like', '%responsable%')->exists())->toBeTrue();
});

it('goes back to the default when a field is cleared', function () {
    AppSetting::savePrivacyController(['nit' => '900.123.456-7']);
    expect(AppSetting::privacyPolicy()['nit'])->toBe('900.123.456-7');

    AppSetting::savePrivacyController(['nit' => '']);

    expect(AppSetting::privacyPolicy()['nit'])->toBeNull();
});

it('validates the data of the controller', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('privacy-settings.update'), ['company' => '', 'contact_email' => 'no-es-correo'])
        ->assertSessionHasErrors(['company', 'contact_email']);
});

it('keeps the privacy settings for the administrator', function () {
    foreach ([User::factory()->receptionist()->create(), User::factory()->doctor()->create(), Patient::factory()->withAccount()->create()->user] as $user) {
        $this->actingAs($user)->get(route('privacy-settings.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('privacy-settings.update'), ['company' => 'X', 'contact_email' => 'a@b.co'])->assertForbidden();
        $this->actingAs($user)->post(route('privacy-settings.publish'))->assertForbidden();
    }

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('privacy-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/Privacy')->where('policy.version', config('privacy.version')));
});

it('asks every patient to accept again when a new version is published', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)->get(route('dashboard'))->assertOk();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('privacy-settings.publish'))
        ->assertSessionHas('success');

    expect(AppSetting::privacyVersion())->toBe('2.0')
        ->and(AppSetting::privacyPolicy()['updated_at'])->toBe(today()->toDateString());

    $this->actingAs($patient->user->refresh())
        ->get(route('dashboard'))
        ->assertRedirect(route('privacy.accept'));

    $this->actingAs($patient->user)
        ->post(route('privacy.accept.store'), ['privacy' => 'on'])
        ->assertRedirect();

    expect($patient->user->refresh()->privacy_policy_version)->toBe('2.0');

    $this->actingAs($patient->user)->get(route('dashboard'))->assertOk();
});

it('registers new patients with the published version', function () {
    $this->actingAs(User::factory()->admin()->create())->post(route('privacy-settings.publish'));
    auth()->logout();

    $this->post(route('register.store'), [
        'name' => 'Ana Prueba',
        'email' => 'ana@example.com',
        'password' => 'password-segura-123',
        'password_confirmation' => 'password-segura-123',
        'privacy' => 'on',
    ]);

    expect(User::query()->where('email', 'ana@example.com')->value('privacy_policy_version'))->toBe('2.0');
});
