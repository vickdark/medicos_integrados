<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

/**
 * What a patient asks about their personal data (Ley 1581 de 2012, art. 8).
 */
enum DataRequestType: string
{
    use HasEnumOptions;

    case Access = 'access';
    case Rectification = 'rectification';
    case Deletion = 'deletion';

    public function label(): string
    {
        return match ($this) {
            self::Access => 'Conocer mis datos',
            self::Rectification => 'Corregir o actualizar mis datos',
            self::Deletion => 'Suprimir mis datos o revocar la autorización',
        };
    }

    /**
     * Business days the clinic has to answer: 10 for queries (art. 14) and 15
     * for claims (art. 15).
     */
    public function responseBusinessDays(): int
    {
        return match ($this) {
            self::Access => 10,
            self::Rectification, self::Deletion => 15,
        };
    }
}
