<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum PaymentStatus: string
{
    use HasEnumOptions;

    case Pending = 'pending';
    case Paid = 'paid';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Paid => 'Pagado',
            self::Voided => 'Anulado',
        };
    }
}
