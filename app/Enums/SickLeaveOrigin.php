<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

/**
 * Origin of a sick leave, which decides who pays for it (EPS, ARL or SOAT).
 */
enum SickLeaveOrigin: string
{
    use HasEnumOptions;

    case GeneralIllness = 'general_illness';
    case WorkAccident = 'work_accident';
    case OccupationalDisease = 'occupational_disease';
    case TrafficAccident = 'traffic_accident';

    public function label(): string
    {
        return match ($this) {
            self::GeneralIllness => 'Enfermedad general',
            self::WorkAccident => 'Accidente de trabajo',
            self::OccupationalDisease => 'Enfermedad laboral',
            self::TrafficAccident => 'Accidente de tránsito',
        };
    }
}
