<?php

namespace App\Enums;

enum ExportFormat: string
{
    case Xlsx = 'xlsx';
    case Pdf = 'pdf';

    public function label(): string
    {
        return match ($this) {
            self::Xlsx => 'Excel',
            self::Pdf => 'PDF',
        };
    }
}
