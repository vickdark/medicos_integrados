<?php

use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['auth.staff_idle_minutes' => 15]);
});

it('closes the session of staff after the idle limit', function (User $user) {
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $this->travel(17)->minutes();

    $this->get(route('dashboard'))->assertRedirect(route('login', ['expired' => 1]));

    $this->assertGuest();
})->with([
    'admin' => fn () => User::factory()->admin()->create(),
    'doctor' => fn () => User::factory()->doctor()->create(),
    'receptionist' => fn () => User::factory()->receptionist()->create(),
]);

it('keeps the session while the staff member is active', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))->assertOk();

    $this->travel(10)->minutes();
    $this->get(route('dashboard'))->assertOk();

    $this->travel(10)->minutes();
    $this->get(route('dashboard'))->assertOk();

    $this->assertAuthenticated();
});

it('counts the keep alive ping as activity', function () {
    $this->actingAs(User::factory()->doctor()->create())->get(route('dashboard'));

    $this->travel(14)->minutes();
    $this->post(route('session.keep-alive'))->assertNoContent();

    $this->travel(14)->minutes();
    $this->get(route('dashboard'))->assertOk();
});

it('does not count the background polling as activity', function () {
    $this->actingAs(User::factory()->receptionist()->create())->get(route('dashboard'));

    $this->travel(10)->minutes();
    $this->withHeader('X-Background', '1')->get(route('dashboard'))->assertOk();

    $this->travel(7)->minutes();
    $this->get(route('dashboard'))->assertRedirect(route('login', ['expired' => 1]));
});

it('answers the keep alive ping of an expired session without redirecting', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'));

    $this->travel(20)->minutes();

    $this->postJson(route('session.keep-alive'))->assertUnauthorized();
});

it('never closes the session of patients', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)->get(route('dashboard'))->assertOk();

    $this->travel(5)->hours();

    $this->get(route('dashboard'))->assertOk();
});

it('can be disabled', function () {
    config(['auth.staff_idle_minutes' => 0]);

    $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))->assertOk();

    $this->travel(5)->hours();

    $this->get(route('dashboard'))->assertOk();
});

it('shares the limit with the browser only for staff', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.idleTimeoutMinutes', 15));

    $this->actingAs(Patient::factory()->withAccount()->create()->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.idleTimeoutMinutes', 0));
});

it('tells the user why the login page is shown', function () {
    $this->get(route('login', ['expired' => 1]))
        ->assertInertia(fn (Assert $page) => $page->component('auth/Login')->where('expired', true));
});
