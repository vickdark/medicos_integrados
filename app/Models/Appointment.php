<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'scheduled_at',
        'status',
        'reason',
        'notes',
        'reminder_sent_at',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'status' => AppointmentStatus::class,
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasOne<Consultation, $this>
     */
    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
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
     * Load whether the appointment has paid or pending payments, which drives
     * its payment indicator.
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeWithPaymentFlags(Builder $query): void
    {
        $query->withExists([
            'payments as has_paid_payment' => fn (Builder $query) => $query->where('status', PaymentStatus::Paid),
            'payments as has_pending_payment' => fn (Builder $query) => $query->where('status', PaymentStatus::Pending),
        ])->withMin(['payments as pending_payment_id' => fn (Builder $query) => $query->where('status', PaymentStatus::Pending)], 'id');
    }

    /**
     * Limit the query to the appointments the given user is allowed to see.
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        match ($user->role) {
            UserRole::Patient => $query->where('patient_id', $user->patient?->id),
            UserRole::Doctor => $query->where('doctor_id', $user->doctor?->id),
            default => null,
        };
    }
}
