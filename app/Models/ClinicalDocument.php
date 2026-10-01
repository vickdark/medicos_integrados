<?php

namespace App\Models;

use App\Actions\Verification\DocumentVerifier;
use App\Enums\ClinicalDocumentType;
use Carbon\CarbonImmutable;
use Database\Factories\ClinicalDocumentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * A consent, sick leave, referral or exam order issued from a consultation.
 * Like the rest of the clinical record, it cannot be changed once issued.
 *
 * @property array<string, mixed> $data
 */
class ClinicalDocument extends Model
{
    /** @use HasFactory<ClinicalDocumentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'consultation_id',
        'type',
        'verification_code',
        'data',
        'issued_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ClinicalDocumentType::class,
            'data' => 'encrypted:array',
        ];
    }

    /**
     * Refuse any change or removal once the document is issued.
     */
    protected static function booted(): void
    {
        static::creating(function (ClinicalDocument $document): void {
            $document->verification_code ??= DocumentVerifier::newCode(DocumentVerifier::DOCUMENT_PREFIX);
        });

        static::updating(function (): void {
            throw new LogicException('Un documento clínico emitido no se puede modificar.');
        });

        static::deleting(function (): void {
            throw new LogicException('Un documento clínico emitido no se puede eliminar.');
        });
    }

    /**
     * @return BelongsTo<Consultation, $this>
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Document number, e.g. "INC-000012".
     *
     * @return Attribute<string, never>
     */
    protected function number(): Attribute
    {
        return Attribute::get(fn (): string => $this->type->prefix().'-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }

    /**
     * Last day of a sick leave, counting the first day.
     */
    public function sickLeaveEndDate(): ?CarbonImmutable
    {
        if ($this->type !== ClinicalDocumentType::SickLeave) {
            return null;
        }

        return CarbonImmutable::parse($this->data['start_date'])->addDays((int) $this->data['days'] - 1);
    }

    /**
     * One line describing the document, for listings.
     */
    public function summary(): string
    {
        return match ($this->type) {
            ClinicalDocumentType::InformedConsent => (string) $this->data['procedure'],
            ClinicalDocumentType::SickLeave => sprintf(
                '%d %s, del %s al %s',
                $this->data['days'],
                (int) $this->data['days'] === 1 ? 'día' : 'días',
                CarbonImmutable::parse($this->data['start_date'])->format('d/m/Y'),
                $this->sickLeaveEndDate()?->format('d/m/Y'),
            ),
            ClinicalDocumentType::Referral => 'A '.$this->data['specialty'],
            ClinicalDocumentType::ExamOrder => implode(', ', (array) $this->data['exams']),
        };
    }
}
