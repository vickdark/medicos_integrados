<?php

use App\Models\Doctor;
use App\Models\Specialty;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the landing page with the specialties and their doctor count', function () {
    $cardiology = Specialty::factory()->create(['name' => 'Cardiología']);
    Doctor::factory()->count(2)->for($cardiology)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('specialties', 1)
            ->where('specialties.0.name', 'Cardiología')
            ->where('specialties.0.doctors_count', 2)
        );
});
