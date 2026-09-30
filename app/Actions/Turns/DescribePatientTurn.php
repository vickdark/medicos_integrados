<?php

namespace App\Actions\Turns;

use App\Enums\AppointmentStatus;
use App\Enums\TurnStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Turn;

/**
 * The patient's place in today's queue, for their portal. When they have an
 * appointment today but no turn yet, it tells them to check in at reception.
 */
class DescribePatientTurn
{
    /**
     * @return array{
     *     turn: array{code: string, status: array{value: string, label: string}, doctor: string, ahead: int, serving: string|null}|null,
     *     appointment: array{scheduled_at: string, doctor: string}|null
     * }|null
     */
    public function handle(Patient $patient): ?array
    {
        $turn = Turn::query()
            ->today()
            ->whereBelongsTo($patient)
            ->where('status', '!=', TurnStatus::Cancelled)
            ->with('doctor.user')
            ->orderByRaw('case when status in (?, ?) then 0 else 1 end', [TurnStatus::Called->value, TurnStatus::Waiting->value])
            ->latest('number')
            ->first();

        $appointment = $turn === null
            ? Appointment::query()
                ->whereBelongsTo($patient)
                ->whereDate('scheduled_at', today())
                ->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])
                ->with('doctor.user')
                ->orderBy('scheduled_at')
                ->first()
            : null;

        if ($turn === null && $appointment === null) {
            return null;
        }

        return [
            'turn' => $turn ? [
                'code' => $turn->code,
                'status' => $turn->status->toOption(),
                'doctor' => $turn->doctor->user->name,
                'ahead' => $turn->peopleAhead(),
                'serving' => $turn->status === TurnStatus::Waiting ? $this->servingFor($turn) : null,
            ] : null,
            'appointment' => $appointment ? [
                'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
                'doctor' => $appointment->doctor->user->name,
            ] : null,
        ];
    }

    /**
     * Code of the turn the same doctor is attending right now.
     */
    private function servingFor(Turn $turn): ?string
    {
        return Turn::query()
            ->today()
            ->where('doctor_id', $turn->doctor_id)
            ->where('status', TurnStatus::Called)
            ->latest('called_at')
            ->first()
            ?->code;
    }
}
