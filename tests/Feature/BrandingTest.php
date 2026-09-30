<?php

use App\Actions\Branding\BrandPalette;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('uses the default color until the administrator changes it', function () {
    expect(AppSetting::brandColor())->toBe(BrandPalette::DEFAULT_COLOR);

    $this->get(route('home'))->assertSee('--brand: '.BrandPalette::DEFAULT_COLOR, false);
});

it('lets the administrator change the color of the whole application', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('branding.update'), ['color' => '#2563eb'])
        ->assertSessionHasNoErrors();

    expect(AppSetting::brandColor())->toBe('#2563EB')
        ->and(AuditLog::query()->where('description', 'like', '%color de la aplicación%#2563EB%')->exists())->toBeTrue();

    auth()->logout();

    $this->get(route('home'))->assertSee('--brand: #2563EB', false);
});

it('shows the settings page with the suggested colors to the administrator', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('branding.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Branding')
            ->where('color', BrandPalette::DEFAULT_COLOR)
            ->has('presets', count(BrandPalette::PRESETS))
        );
});

it('rejects invalid or unreadable colors', function (string $color) {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('branding.update'), ['color' => $color])
        ->assertSessionHasErrors('color');

    expect(AppSetting::brandColor())->toBe(BrandPalette::DEFAULT_COLOR);
})->with(['not a color', '#12345', 'red', '#FFFF00', '#FFFFFF']);

it('restores the original color', function () {
    AppSetting::setBrandColor('#DC2626');

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('branding.destroy'))
        ->assertRedirect();

    expect(AppSetting::brandColor())->toBe(BrandPalette::DEFAULT_COLOR);
});

it('does not let anyone but the administrator change the color', function () {
    foreach (['receptionist', 'doctor', 'patient'] as $state) {
        $user = User::factory()->{$state}()->create();

        $this->actingAs($user)->get(route('branding.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('branding.update'), ['color' => '#2563EB'])->assertForbidden();
        $this->actingAs($user)->delete(route('branding.destroy'))->assertForbidden();
    }

    expect(AppSetting::brandColor())->toBe(BrandPalette::DEFAULT_COLOR);
});

it('renders the documents with the chosen color', function () {
    AppSetting::setBrandColor('#7C3AED');

    $html = view('pdf.invoice', [
        'payment' => Payment::factory()->create()->load(['patient', 'appointment']),
        'number' => 'F-000001',
        'generatedAt' => now(),
        ...BrandPalette::documentColors(AppSetting::brandColor()),
    ])->render();

    expect($html)->toContain('#7C3AED')->not->toContain('#0d9488');
});

it('calculates readable contrast and tints of the brand color', function () {
    expect(BrandPalette::hasEnoughContrast('#0D9488'))->toBeTrue()
        ->and(BrandPalette::hasEnoughContrast('#FFFF00'))->toBeFalse()
        ->and(BrandPalette::tint('#000000', 0.5))->toBe('#808080')
        ->and(BrandPalette::shade('#FFFFFF', 0.5))->toBe('#808080')
        ->and(BrandPalette::withoutHash('#0d9488'))->toBe('0D9488');
});
