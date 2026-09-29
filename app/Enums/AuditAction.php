<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum AuditAction: string
{
    use HasEnumOptions;

    case Viewed = 'viewed';
    case Created = 'created';
    case Updated = 'updated';
    case Uploaded = 'uploaded';
    case Downloaded = 'downloaded';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Viewed => 'Consultó',
            self::Created => 'Creó',
            self::Updated => 'Modificó',
            self::Uploaded => 'Subió',
            self::Downloaded => 'Descargó',
            self::Deleted => 'Eliminó',
        };
    }
}
