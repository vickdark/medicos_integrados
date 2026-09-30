<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

/**
 * Documents a doctor issues from a consultation, besides the prescription.
 */
enum ClinicalDocumentType: string
{
    use HasEnumOptions;

    case InformedConsent = 'consent';
    case SickLeave = 'sick_leave';
    case Referral = 'referral';
    case ExamOrder = 'exam_order';

    public function label(): string
    {
        return match ($this) {
            self::InformedConsent => 'Consentimiento informado',
            self::SickLeave => 'Incapacidad médica',
            self::Referral => 'Remisión',
            self::ExamOrder => 'Orden de exámenes',
        };
    }

    /**
     * Prefix of the document number, e.g. "INC-000012".
     */
    public function prefix(): string
    {
        return match ($this) {
            self::InformedConsent => 'CI',
            self::SickLeave => 'INC',
            self::Referral => 'REM',
            self::ExamOrder => 'ORD',
        };
    }
}
