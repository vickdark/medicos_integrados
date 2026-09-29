<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum UserRole: string
{
    use HasEnumOptions;

    case Admin = 'admin';
    case Doctor = 'doctor';
    case Receptionist = 'receptionist';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Doctor => 'Médico',
            self::Receptionist => 'Recepción',
            self::Patient => 'Paciente',
        };
    }

    /**
     * Roles that belong to clinic personnel.
     */
    public function isStaff(): bool
    {
        return $this !== self::Patient;
    }
}
