<?php

namespace App\Actions\Payments;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;

/**
 * Keeps the pending charge of an appointment in the payments module, so reception
 * can settle it from there instead of typing the payment again.
 */
class OpenAppointmentCharge
{
    /**
     * Open a pending payment for the appointment's consultation fee, unless the
     * appointment already has a payment or the doctor charges nothing.
     */
    public function open(Appointment $appointment): ?Payment
    {
        $appointment->loadMissing('doctor.user');

        $fee = (float) $appointment->doctor->consultation_fee;

        $alreadyCharged = $appointment->payments()
            ->where('status', '!=', PaymentStatus::Voided)
            ->exists();

        if ($fee <= 0 || $alreadyCharged) {
            return null;
        }

        return $appointment->payments()->create([
            'patient_id' => $appointment->patient_id,
            'amount' => $fee,
            'method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Pending,
            'concept' => 'Consulta médica · '.$appointment->doctor->user->name,
        ]);
    }

    /**
     * Keep the pending charge in line with the doctor who now attends the
     * appointment. Charges already paid are left untouched.
     */
    public function syncDoctor(Appointment $appointment): void
    {
        $appointment->load('doctor.user');

        $fee = (float) $appointment->doctor->consultation_fee;

        if ($fee <= 0) {
            $this->void($appointment);

            return;
        }

        $pending = $appointment->payments()->where('status', PaymentStatus::Pending)->get();

        if ($pending->isEmpty()) {
            if ($appointment->status !== AppointmentStatus::Requested) {
                $this->open($appointment);
            }

            return;
        }

        $pending->each(fn (Payment $payment) => $payment->update([
            'amount' => $fee,
            'concept' => 'Consulta médica · '.$appointment->doctor->user->name,
        ]));
    }

    /**
     * Void the pending charges of a cancelled appointment. Paid ones are kept.
     */
    public function void(Appointment $appointment): void
    {
        $appointment->payments()
            ->where('status', PaymentStatus::Pending)
            ->update(['status' => PaymentStatus::Voided]);
    }
}
