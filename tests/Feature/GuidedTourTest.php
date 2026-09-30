<?php

use App\Models\Patient;
use App\Models\TourView;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

const DEVICE_A = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
const DEVICE_B = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

it('shows the guided tour to a patient the first time on a device', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->withUnencryptedCookie('device_id', DEVICE_A)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('showTour', true));
});

it('remembers on the database that the tour was seen on that device', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->withUnencryptedCookie('device_id', DEVICE_A)
        ->post(route('tour.store'))
        ->assertRedirect();

    expect(TourView::query()->where('user_id', $patient->user_id)->where('device_id', DEVICE_A)->count())->toBe(1);

    $this->actingAs($patient->user)
        ->withUnencryptedCookie('device_id', DEVICE_A)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('showTour', false));
});

it('does not duplicate the record when the tour is completed twice', function () {
    $patient = Patient::factory()->withAccount()->create();

    foreach ([1, 2] as $attempt) {
        $this->actingAs($patient->user)
            ->withUnencryptedCookie('device_id', DEVICE_A)
            ->post(route('tour.store'));
    }

    expect(TourView::query()->count())->toBe(1);
});

it('shows the tour again on a different device', function () {
    $patient = Patient::factory()->withAccount()->create();
    TourView::factory()->create(['user_id' => $patient->user_id, 'device_id' => DEVICE_A]);

    $this->actingAs($patient->user)
        ->withUnencryptedCookie('device_id', DEVICE_B)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('showTour', true));

    $this->actingAs($patient->user)
        ->withUnencryptedCookie('device_id', DEVICE_A)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('showTour', false));
});

it('keeps the record per user, so another account on the same device sees the tour', function () {
    $first = Patient::factory()->withAccount()->create();
    $second = Patient::factory()->withAccount()->create();
    TourView::factory()->create(['user_id' => $first->user_id, 'device_id' => DEVICE_A]);

    $this->actingAs($second->user)
        ->withUnencryptedCookie('device_id', DEVICE_A)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('showTour', true));
});

it('does not show the tour to staff', function () {
    $this->actingAs(User::factory()->receptionist()->create())
        ->withUnencryptedCookie('device_id', DEVICE_A)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('showTour', false));
});

it('assigns a device identifier cookie to new visitors', function () {
    $this->get(route('home'))->assertCookie('device_id');
});

it('requires authentication to record the tour', function () {
    $this->post(route('tour.store'))->assertRedirect(route('login'));
});
