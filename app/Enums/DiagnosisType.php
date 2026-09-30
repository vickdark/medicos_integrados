<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

/**
 * Type of the main diagnosis of a consultation, as the RIPS classify it.
 */
enum DiagnosisType: string
{
    use HasEnumOptions;

    case Impression = '1';
    case ConfirmedNew = '2';
    case ConfirmedRepeated = '3';

    public function label(): string
    {
        return match ($this) {
            self::Impression => 'Impresión diagnóstica',
            self::ConfirmedNew => 'Confirmado nuevo',
            self::ConfirmedRepeated => 'Confirmado repetido',
        };
    }
}
