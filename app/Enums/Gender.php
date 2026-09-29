<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum Gender: string
{
    use HasEnumOptions;

    case Female = 'female';
    case Male = 'male';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Female => 'Femenino',
            self::Male => 'Masculino',
            self::Other => 'Otro',
        };
    }
}
