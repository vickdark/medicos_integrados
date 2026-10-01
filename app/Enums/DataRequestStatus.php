<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum DataRequestStatus: string
{
    use HasEnumOptions;

    case Pending = 'pending';
    case Resolved = 'resolved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Resolved => 'Atendida',
            self::Rejected => 'Rechazada',
        };
    }
}
