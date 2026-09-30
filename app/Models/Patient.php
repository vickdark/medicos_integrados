<?php

namespace App\Models;

use App\Enums\AffiliationType;
use App\Enums\DocumentType;
use App\Enums\Gender;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'document_type',
        'document_number',
        'email',
        'phone',
        'birth_date',
        'gender',
        'address',
        'blood_type',
        'insurer_id',
        'affiliation_type',
        'allergies',
        'chronic_conditions',
        'medical_background',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'full_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            'gender' => Gender::class,
            'document_type' => DocumentType::class,
            'affiliation_type' => AffiliationType::class,
            'allergies' => 'encrypted',
            'chronic_conditions' => 'encrypted',
            'medical_background' => 'encrypted',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->first_name} {$this->last_name}"));
    }

    /**
     * Whether a doctor has already attended the patient at least once.
     */
    public function hasBeenAttended(): bool
    {
        return $this->consultations()->exists();
    }

    /**
     * Whether the basic data a patient provides on first access is complete.
     */
    public function hasCompleteBasicProfile(): bool
    {
        return filled($this->document_number)
            && $this->document_type !== null
            && filled($this->phone)
            && $this->birth_date !== null
            && $this->gender !== null;
    }

    /**
     * @return BelongsTo<Insurer, $this>
     */
    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<Consultation, $this>
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Turn, $this>
     */
    public function turns(): HasMany
    {
        return $this->hasMany(Turn::class);
    }

    /**
     * Limit the query to patients the given doctor has appointments or consultations with.
     *
     * @param  Builder<Patient>  $query
     */
    public function scopeTreatedBy(Builder $query, Doctor $doctor): void
    {
        $query->where(function (Builder $query) use ($doctor): void {
            $query->whereHas('appointments', fn (Builder $query) => $query->whereBelongsTo($doctor))
                ->orWhereHas('consultations', fn (Builder $query) => $query->whereBelongsTo($doctor))
                ->orWhereHas('turns', fn (Builder $query) => $query->whereBelongsTo($doctor));
        });
    }

    /**
     * Filter patients by name, document or email.
     *
     * @param  Builder<Patient>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('document_number', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }
}
