<?php

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lists the specialties for the admin', function () {
    Specialty::factory()->count(3)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('specialties.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('specialties/Index')
            ->has('specialties.data', 3)
        );
});

it('creates and updates specialties', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('specialties.store'), ['name' => 'Nefrología', 'description' => 'Riñones'])
        ->assertRedirect(route('specialties.index'));

    $specialty = Specialty::query()->where('name', 'Nefrología')->sole();

    $this->actingAs($admin)
        ->put(route('specialties.update', $specialty), ['name' => 'Nefrología pediátrica'])
        ->assertRedirect(route('specialties.index'));

    expect($specialty->refresh()->name)->toBe('Nefrología pediátrica');
});

it('rejects duplicated specialty names', function () {
    Specialty::factory()->create(['name' => 'Pediatría']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('specialties.store'), ['name' => 'Pediatría'])
        ->assertSessionHasErrors('name');
});

it('deletes only specialties without doctors', function () {
    $admin = User::factory()->admin()->create();
    $unused = Specialty::factory()->create();
    $inUse = Doctor::factory()->create()->specialty;

    $this->actingAs($admin)->delete(route('specialties.destroy', $unused))->assertRedirect();
    $this->actingAs($admin)->delete(route('specialties.destroy', $inUse))->assertForbidden();

    expect(Specialty::query()->whereKey($unused->id)->exists())->toBeFalse()
        ->and(Specialty::query()->whereKey($inUse->id)->exists())->toBeTrue();
});

it('forbids non admins from managing specialties', function () {
    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('specialties.index'))
        ->assertForbidden();
});
