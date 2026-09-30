<?php

use App\Models\Insurer;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lets the administrator manage the insurers catalog', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('insurers.store'), ['name' => 'Nueva EPS', 'code' => 'EPS037'])
        ->assertRedirect(route('insurers.index'));

    $insurer = Insurer::query()->sole();

    $this->actingAs($admin)
        ->put(route('insurers.update', $insurer), ['name' => 'Nueva EPS S.A.', 'code' => 'EPS037'])
        ->assertRedirect(route('insurers.index'));

    $this->actingAs($admin)
        ->get(route('insurers.index', ['search' => 'eps037']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('insurers/Index')
            ->where('insurers.data.0.name', 'Nueva EPS S.A.')
            ->where('insurers.data.0.can_delete', true)
        );

    $this->actingAs($admin)->delete(route('insurers.destroy', $insurer))->assertRedirect();

    expect(Insurer::query()->count())->toBe(0);
});

it('rejects duplicated insurers and keeps the ones assigned to patients', function () {
    $admin = User::factory()->admin()->create();
    $insurer = Insurer::factory()->create(['name' => 'EPS Sura', 'code' => 'EPS010']);
    Patient::factory()->create(['insurer_id' => $insurer->id]);

    $this->actingAs($admin)
        ->post(route('insurers.store'), ['name' => 'EPS Sura', 'code' => 'EPS010'])
        ->assertSessionHasErrors(['name', 'code']);

    $this->actingAs($admin)->delete(route('insurers.destroy', $insurer))->assertForbidden();

    expect($insurer->fresh())->not->toBeNull();
});

it('keeps the insurers catalog for administrators only', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->get(route('insurers.index'))->assertForbidden();
    $this->actingAs($user)->post(route('insurers.store'), ['name' => 'Otra EPS'])->assertForbidden();
})->with(['receptionist', 'doctor', 'patient']);
