<?php

namespace App\Models;

use App\Enums\DiagnosisType;
use Database\Factories\ConsultationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Consultation extends Model
{
    /**
     * Fields of the clinical record. Once saved they cannot change: corrections
     * are made with clarifying notes that keep the original visible.
     *
     * @var list<string>
     */
    public const CLINICAL_FIELDS = [
        'patient_id',
        'doctor_id',
        'consulted_at',
        'reason',
        'symptoms',
        'diagnosis',
        'primary_diagnosis_id',
        'diagnosis_type',
        'treatment',
        'notes',
        'weight_kg',
        'height_cm',
        'blood_pressure',
        'temperature_c',
        'heart_rate',
    ];

    /** @use HasFactory<ConsultationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'consulted_at',
        'reason',
        'symptoms',
        'diagnosis',
        'primary_diagnosis_id',
        'diagnosis_type',
        'treatment',
        'notes',
        'weight_kg',
        'height_cm',
        'blood_pressure',
        'temperature_c',
        'heart_rate',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consulted_at' => 'datetime',
            'reason' => 'encrypted',
            'symptoms' => 'encrypted',
            'diagnosis' => 'encrypted',
            'diagnosis_type' => DiagnosisType::class,
            'treatment' => 'encrypted',
            'notes' => 'encrypted',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'temperature_c' => 'decimal:1',
            'heart_rate' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Main CIE-10 diagnosis. Consultations recorded before coding existed have none.
     *
     * @return BelongsTo<Diagnosis, $this>
     */
    public function primaryDiagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class, 'primary_diagnosis_id');
    }

    /**
     * Up to three related CIE-10 diagnoses, in the order the doctor entered them.
     *
     * @return BelongsToMany<Diagnosis, $this>
     */
    public function relatedDiagnoses(): BelongsToMany
    {
        return $this->belongsToMany(Diagnosis::class)
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /**
     * Refuse changes to the clinical record and its removal once saved.
     */
    protected static function booted(): void
    {
        static::updating(function (Consultation $consultation): void {
            if ($consultation->isDirty(self::CLINICAL_FIELDS)) {
                throw new LogicException('Una consulta registrada no se puede modificar; agrega una nota aclaratoria.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Una consulta registrada no se puede eliminar.');
        });
    }

    /**
     * Clarifying notes, oldest first.
     *
     * @return HasMany<ConsultationAddendum, $this>
     */
    public function addenda(): HasMany
    {
        return $this->hasMany(ConsultationAddendum::class)->oldest('id');
    }

    /**
     * Consents, sick leaves, referrals and exam orders issued from the consultation.
     *
     * @return HasMany<ClinicalDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ClinicalDocument::class)->oldest('id');
    }

    /**
     * @return HasMany<Prescription, $this>
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * @return HasMany<ConsultationAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ConsultationAttachment::class);
    }
}
