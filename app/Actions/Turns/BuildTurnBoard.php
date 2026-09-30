<?php

namespace App\Actions\Turns;

use App\Enums\TurnStatus;
use App\Models\Turn;

/**
 * What the waiting room screen shows: the turns being attended and the next
 * ones in line. It only exposes turn codes and doctor names, never patients.
 */
class BuildTurnBoard
{
    public const NEXT_LIMIT = 10;

    /**
     * Seconds during which a freshly called turn is highlighted on the screen.
     */
    public const HIGHLIGHT_SECONDS = 30;

    /**
     * @return array{
     *     serving: list<array{id: int, code: string, doctor: string, specialty: string, recent: bool}>,
     *     next: list<array{id: int, code: string, doctor: string}>,
     *     waiting_count: int,
     *     updated_at: string
     * }
     */
    public function handle(): array
    {
        $serving = Turn::query()
            ->today()
            ->where('status', TurnStatus::Called)
            ->with(['doctor.user', 'doctor.specialty'])
            ->latest('called_at')
            ->get()
            ->map(fn (Turn $turn): array => [
                'id' => $turn->id,
                'code' => $turn->code,
                'doctor' => $turn->doctor->user->name,
                'specialty' => $turn->doctor->specialty->name,
                'recent' => $turn->called_at !== null
                    && $turn->called_at->greaterThan(now()->subSeconds(self::HIGHLIGHT_SECONDS)),
            ])
            ->all();

        $waiting = Turn::query()
            ->today()
            ->where('status', TurnStatus::Waiting);

        $next = (clone $waiting)
            ->with('doctor.user')
            ->orderBy('number')
            ->limit(self::NEXT_LIMIT)
            ->get()
            ->map(fn (Turn $turn): array => [
                'id' => $turn->id,
                'code' => $turn->code,
                'doctor' => $turn->doctor->user->name,
            ])
            ->all();

        return [
            'serving' => $serving,
            'next' => $next,
            'waiting_count' => (clone $waiting)->count(),
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
