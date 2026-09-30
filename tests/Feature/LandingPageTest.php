<?php

use App\Models\Doctor;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the public landing page without listing the specialties', function () {
    Doctor::factory()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('canRegister')
            ->missing('specialties')
        );
});

it('lets a guest see the landing page and a signed in user too', function () {
    $this->get(route('home'))->assertOk();

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk();
});
