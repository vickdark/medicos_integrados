<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

/**
 * How soon a referral or an exam order must be attended.
 */
enum CarePriority: string
{
    use HasEnumOptions;

    case Routine = 'routine';
    case Priority = 'priority';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Routine => 'Rutinaria',
            self::Priority => 'Prioritaria',
            self::Urgent => 'Urgente',
        };
    }
}
