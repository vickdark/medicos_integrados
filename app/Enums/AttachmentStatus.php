<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

/**
 * State of a consultation attachment. Attachments are never deleted: a wrong
 * file is either replaced by a corrected one or voided, always with a reason.
 */
enum AttachmentStatus: string
{
    use HasEnumOptions;

    case Active = 'active';
    case Corrected = 'corrected';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Vigente',
            self::Corrected => 'Corregido',
            self::Voided => 'Anulado',
        };
    }

    /**
     * States an active attachment can move to.
     *
     * @return list<self>
     */
    public static function closing(): array
    {
        return [self::Corrected, self::Voided];
    }
}
