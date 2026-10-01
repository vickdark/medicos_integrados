<?php

use App\Models\AppSetting;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, mixed>
 */
function landingPayload(array $overrides = []): array
{
    return array_replace_recursive(AppSetting::landingContent(), $overrides);
}

it('shows the current texts by default', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('content.contact.phone', '(01) 000-0000')
            ->where('content.about.title', 'Un centro médico que te conoce y te cuida')
            ->has('content.values', 3)
            ->has('content.services.items', 6));
});

it('lets the administrator edit the contact data and the texts of the landing page', function () {
    $payload = landingPayload([
        'contact' => ['phone' => '3001234567', 'email' => 'info@clinica.test', 'address' => 'Calle 1 # 2-3'],
        'about' => ['title' => 'Somos la clínica del barrio'],
        'values' => [1 => ['title' => 'Respeto']],
        'services' => ['items' => [5 => ['title' => 'Vacunación']]],
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('landing-content.update'), $payload)
        ->assertSessionHasNoErrors();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('content.contact.phone', '3001234567')
            ->where('content.contact.address', 'Calle 1 # 2-3')
            ->where('content.about.title', 'Somos la clínica del barrio')
            ->where('content.values.1.title', 'Respeto')
            ->where('content.values.0.title', 'Cercanía')
            ->where('content.services.items.5.title', 'Vacunación'));
});

it('hides the contact data left empty', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('landing-content.update'), landingPayload(['contact' => ['phone' => null, 'email' => null, 'address' => null]]))
        ->assertSessionHasNoErrors();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('content.contact.phone', '')
            ->where('content.contact.email', '')
            ->where('content.contact.address', ''));
});

it('validates the texts', function () {
    $payload = landingPayload(['about' => ['title' => ''], 'contact' => ['email' => 'no-es-correo']]);
    $payload['values'] = array_slice($payload['values'], 0, 2);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('landing-content.update'), $payload)
        ->assertSessionHasErrors(['about.title', 'contact.email', 'values']);
});

it('restores the original texts', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put(route('landing-content.update'), landingPayload(['about' => ['title' => 'Otro título']]));
    expect(AppSetting::landingContent()['about']['title'])->toBe('Otro título');

    $this->actingAs($admin)->delete(route('landing-content.destroy'))->assertSessionHas('success');

    expect(AppSetting::landingContent()['about']['title'])->toBe(config('landing.about.title'));
});

it('keeps the landing page settings for the administrator', function () {
    foreach ([User::factory()->receptionist()->create(), User::factory()->doctor()->create(), Patient::factory()->withAccount()->create()->user] as $user) {
        $this->actingAs($user)->get(route('landing-content.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('landing-content.update'), landingPayload())->assertForbidden();
        $this->actingAs($user)->delete(route('landing-content.destroy'))->assertForbidden();
    }

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('landing-content.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/Landing')->has('content.values', 3));
});
