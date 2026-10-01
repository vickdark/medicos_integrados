<?php

namespace App\Models;

use App\Actions\Verification\DocumentVerifier;
use Database\Factories\ClinicalHistoryExportFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * A clinical history PDF that was generated: who generated it, for which period
 * and which consultations it included. It backs the QR printed on the PDF and
 * cannot be changed or removed.
 */
class ClinicalHistoryExport extends Model
{
    /** @use HasFactory<ClinicalHistoryExportFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'issued_by',
        'issued_by_name',
        'issued_by_role',
        'verification_code',
        'period_from',
        'period_to',
        'period_label',
        'consultation_ids',
        'includes_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_from' => 'date:Y-m-d',
            'period_to' => 'date:Y-m-d',
            'consultation_ids' => 'array',
            'includes_notes' => 'boolean',
        ];
    }

    /**
     * Give the export its code, and refuse any later change or removal.
     */
    protected static function booted(): void
    {
        static::creating(function (ClinicalHistoryExport $export): void {
            $export->verification_code ??= DocumentVerifier::newCode(DocumentVerifier::HISTORY_PREFIX);
        });

        static::updating(function (): void {
            throw new LogicException('Una historia clínica emitida no se puede modificar.');
        });

        static::deleting(function (): void {
            throw new LogicException('Una historia clínica emitida no se puede eliminar.');
        });
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Number printed on the PDF, e.g. "HC-000045".
     *
     * @return Attribute<string, never>
     */
    protected function number(): Attribute
    {
        return Attribute::get(fn (): string => 'HC-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }
}
