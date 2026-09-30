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

it('prefills the payment form from an appointment', function () {
    $appointment = Appointment::factory()->confirmed()->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('payments.create', ['appointment_id' => $appointment->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedPatientId', $appointment->patient_id)
            ->where('prefill.appointment_id', $appointment->id)
            ->where('prefill.amount', $appointment->doctor->consultation_fee)
            ->where('prefill.concept', 'Consulta médica · '.$appointment->doctor->user->name)
        );
});

it('lets reception mark a pending payment as paid', function () {
    $receptionist = User::factory()->receptionist()->create();
    $payment = Payment::factory()->pending()->create();

    $this->actingAs($receptionist)
        ->patch(route('payments.paid', $payment), ['method' => 'card', 'paid_at' => now()->toDateString(), 'reference' => 'TX-1'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->method->value)->toBe('card')
        ->and($payment->reference)->toBe('TX-1')
        ->and($payment->recorded_by)->toBe($receptionist->id);
});

it('does not allow marking as paid a payment that is not pending or by non-staff', function () {
    $paid = Payment::factory()->create();
    $pending = Payment::factory()->pending()->create();
    $body = ['method' => 'cash', 'paid_at' => now()->toDateString()];

    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('payments.paid', $paid), $body)->assertForbidden();
    $this->actingAs(User::factory()->create())
        ->patch(route('payments.paid', $pending), $body)->assertForbidden();
    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('payments.paid', $pending), ['method' => 'cash', 'paid_at' => now()->addDay()->toDateString()])
        ->assertSessionHasErrors('paid_at');

    expect($pending->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('lets reception void a pending or paid payment keeping the reason', function () {
    $receptionist = User::factory()->receptionist()->create();
    $pending = Payment::factory()->pending()->create(['notes' => null]);
    $paid = Payment::factory()->create(['notes' => 'Pago en ventanilla']);

    $this->actingAs($receptionist)
        ->patch(route('payments.void', $pending), ['reason' => 'Cobro duplicado'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    $this->actingAs($receptionist)
        ->patch(route('payments.void', $paid), ['reason' => 'Error de monto'])
        ->assertRedirect();

    expect($pending->fresh()->status)->toBe(PaymentStatus::Voided)
        ->and($pending->fresh()->notes)->toBe('Anulado: Cobro duplicado')
        ->and($paid->fresh()->status)->toBe(PaymentStatus::Voided)
        ->and($paid->fresh()->notes)->toBe("Pago en ventanilla\nAnulado: Error de monto");
});

it('does not allow voiding without a reason, an already voided payment or by non-staff', function () {
    $pending = Payment::factory()->pending()->create();
    $voided = Payment::factory()->create(['status' => PaymentStatus::Voided]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('payments.void', $pending), ['reason' => ''])
        ->assertSessionHasErrors('reason');
    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('payments.void', $voided), ['reason' => 'Otra vez'])
        ->assertForbidden();
    $this->actingAs(User::factory()->create())
        ->patch(route('payments.void', $pending), ['reason' => 'No autorizado'])
        ->assertForbidden();

    expect($pending->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('prefills the payment form from an existing payment row', function () {
    $source = Payment::factory()->create(['concept' => 'Control mensual', 'amount' => '75.00', 'method' => 'card']);

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('payments.create', ['payment_id' => $source->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedPatientId', $source->patient_id)
            ->where('prefill.concept', 'Control mensual')
            ->where('prefill.amount', '75.00')
            ->where('prefill.method', 'card')
            ->where('prefill.appointment_id', null)
        );
});

it('lists the appointment of each payment so it can be settled from the table', function () {
    $appointment = Appointment::factory()->confirmed()->create();
    Payment::factory()->pending()->create(['appointment_id' => $appointment->id, 'patient_id' => $appointment->patient_id]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('payments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('payments.data.0.appointment.id', $appointment->id)
            ->where('payments.data.0.appointment.doctor', $appointment->doctor->user->name)
            ->where('payments.data.0.can.mark_paid', true)
        );
});

it('opens a pending charge with the doctor fee when reception confirms a requested appointment', function () {
    $appointment = Appointment::factory()->create(['doctor_id' => Doctor::factory()->create(['consultation_fee' => 60])->id]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('appointments.status', $appointment), ['status' => 'confirmed'])
        ->assertRedirect();

    $payment = Payment::query()->sole();

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->appointment_id)->toBe($appointment->id)
        ->and($payment->patient_id)->toBe($appointment->patient_id)
        ->and($payment->amount)->toBe('60.00');
});

it('opens a pending charge when reception schedules an appointment and does not duplicate it', function () {
    $doctor = Doctor::factory()->create(['consultation_fee' => 40]);
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->confirmed()->create(['doctor_id' => $doctor->id, 'patient_id' => $patient->id]);
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)->patch(route('appointments.status', $appointment), ['status' => 'confirmed']);
    $this->actingAs($receptionist)->patch(route('appointments.status', $appointment), ['status' => 'confirmed']);

    expect(Payment::query()->count())->toBe(1);
});

it('voids the pending charge when the appointment is cancelled but keeps paid ones', function () {
    $pendingAppointment = Appointment::factory()->confirmed()->create();
    $paidAppointment = Appointment::factory()->confirmed()->create();
    $pending = Payment::factory()->pending()->create(['appointment_id' => $pendingAppointment->id, 'patient_id' => $pendingAppointment->patient_id]);
    $paid = Payment::factory()->create(['appointment_id' => $paidAppointment->id, 'patient_id' => $paidAppointment->patient_id]);
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)->patch(route('appointments.status', $pendingAppointment), ['status' => 'cancelled']);
    $this->actingAs($receptionist)->patch(route('appointments.status', $paidAppointment), ['status' => 'cancelled']);

    expect($pending->fresh()->status)->toBe(PaymentStatus::Voided)
        ->and($paid->fresh()->status)->toBe(PaymentStatus::Paid);
});

it('does not open a charge for doctors with no fee', function () {
    $appointment = Appointment::factory()->create(['doctor_id' => Doctor::factory()->create(['consultation_fee' => 0])->id]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('appointments.status', $appointment), ['status' => 'confirmed']);

    expect(Payment::query()->count())->toBe(0);
});

it('backfills pending charges for appointments that have no payment', function () {
    $withoutPayment = Appointment::factory()->confirmed()->create();
    $withPayment = Appointment::factory()->completed()->create();
    Payment::factory()->create(['appointment_id' => $withPayment->id, 'patient_id' => $withPayment->patient_id]);
    Appointment::factory()->cancelled()->create();

    $this->artisan('payments:open-charges')->assertSuccessful();

    expect(Payment::query()->where('appointment_id', $withoutPayment->id)->sole()->status)->toBe(PaymentStatus::Pending)
        ->and(Payment::query()->count())->toBe(2);
});

it('lists pending payments first and preloads the payment to settle from an appointment', function () {
    $appointment = Appointment::factory()->confirmed()->create();
    Payment::factory()->create(['paid_at' => now()->toDateString()]);
    $pending = Payment::factory()->pending()->create(['appointment_id' => $appointment->id, 'patient_id' => $appointment->patient_id]);
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)
        ->get(route('payments.index', ['pay' => $pending->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('payments.data.0.id', $pending->id)
            ->where('settling.id', $pending->id)
            ->where('settling.appointment.id', $appointment->id)
        );

    $feed = route('appointments.calendar', [
        'from' => $appointment->scheduled_at->copy()->subDay()->toDateString(),
        'to' => $appointment->scheduled_at->copy()->addDay()->toDateString(),
    ]);

    $this->actingAs($receptionist)->getJson($feed)
        ->assertJsonPath('data.0.pending_payment_id', $pending->id)
        ->assertJsonPath('data.0.can.settle_payment', true);
});

it('shows the details of a payment to reception and to its own patient only', function () {
    $appointment = Appointment::factory()->confirmed()->create();
    $payment = Payment::factory()->create(['appointment_id' => $appointment->id, 'patient_id' => $appointment->patient_id, 'reference' => 'REF-9']);

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('payments.show', $payment))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/Show')
            ->where('payment.reference', 'REF-9')
            ->where('payment.appointment.id', $appointment->id)
            ->where('can.view_patient', true)
        );

    $owner = Patient::factory()->withAccount()->create();
    $own = Payment::factory()->create(['patient_id' => $owner->id]);

    $this->actingAs($owner->user)->get(route('payments.show', $own))->assertOk();
    $this->actingAs($owner->user)->get(route('payments.show', $payment))->assertForbidden();
    $this->actingAs($appointment->doctor->user)->get(route('payments.show', $payment))->assertForbidden();
});
