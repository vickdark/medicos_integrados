<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum AppointmentStatus: string
{
    use HasEnumOptions;

    case Requested = 'requested';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Solicitada',
            self::Confirmed => 'Confirmada',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }

    /**
     * Whether the appointment is still pending to happen.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Requested, self::Confirmed], true);
    }
}
