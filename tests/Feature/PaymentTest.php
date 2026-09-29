<?php

use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lets reception record a manual payment', function () {
    $receptionist = User::factory()->receptionist()->create();
    $appointment = Appointment::factory()->completed()->create();

    $this->actingAs($receptionist)
        ->post(route('payments.store'), [
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'amount' => '50.00',
            'method' => 'cash',
            'status' => 'paid',
            'concept' => 'Consulta médica',
            'paid_at' => now()->toDateString(),
        ])
        ->assertRedirect(route('patients.show', $appointment->patient_id))
        ->assertSessionHasNoErrors();

    $payment = Payment::query()->sole();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->amount)->toBe('50.00')
        ->and($payment->recorded_by)->toBe($receptionist->id);
});

it('does not store a payment date for pending payments', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('payments.store'), [
            'patient_id' => Patient::factory()->create()->id,
            'amount' => '30',
            'method' => 'transfer',
            'status' => 'pending',
            'concept' => 'Laboratorio',
            'paid_at' => now()->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect(Payment::query()->sole()->paid_at)->toBeNull();
});

it('requires the payment date when the payment is already paid', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('payments.store'), [
            'patient_id' => Patient::factory()->create()->id,
            'amount' => '30',
            'method' => 'cash',
            'status' => 'paid',
            'concept' => 'Consulta',
        ])
        ->assertSessionHasErrors('paid_at');
});

it('forbids doctors and patients from recording payments', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)->get(route('payments.create'))->assertForbidden();
    $this->actingAs(Doctor::factory()->create()->user)->get(route('payments.create'))->assertForbidden();
});

it('shows the patient only their own payments', function () {
    $patient = Patient::factory()->withAccount()->create();
    Payment::factory()->count(2)->for($patient)->create();
    Payment::factory()->pending()->for($patient)->create(['amount' => 45]);
    Payment::factory()->count(3)->create();

    $this->actingAs($patient->user)
        ->get(route('payments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/Index')
            ->has('payments.data', 3)
            ->where('totals.pending', 45)
            ->where('can.create', false)
        );
});
