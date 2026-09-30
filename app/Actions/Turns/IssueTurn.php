<?php

namespace App\Actions\Turns;

use App\Enums\TurnStatus;
use App\Models\Turn;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Hands out the next number of the day's queue. Numbers are shared by all the
 * doctors, so the waiting room screen never shows the same number twice.
 */
class IssueTurn
{
    private const ATTEMPTS = 3;

    /**
     * @param  array{patient_id: int, doctor_id: int, appointment_id?: int|null}  $attributes
     */
    public function handle(array $attributes, ?int $createdBy = null): Turn
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($attributes, $createdBy): Turn {
                    $today = today();

                    $last = Turn::query()
                        ->whereDate('turn_date', $today)
                        ->lockForUpdate()
                        ->max('number');

                    return Turn::query()->create([
                        ...$attributes,
                        'turn_date' => $today,
                        'number' => ((int) $last) + 1,
                        'status' => TurnStatus::Waiting,
                        'created_by' => $createdBy,
                    ]);
                });
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= self::ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }
}
