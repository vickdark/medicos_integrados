<?php

namespace App\Models;

use App\Enums\TurnStatus;
use App\Enums\UserRole;
use Database\Factories\TurnFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A place in the day's waiting queue of a doctor. Walk-in patients get one at
 * reception, and so do patients with an appointment when they arrive.
 */
class Turn extends Model
{
    /** @use HasFactory<TurnFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'turn_date',
        'number',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'status',
        'called_at',
        'finished_at',
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
            'turn_date' => 'date:Y-m-d',
            'number' => 'integer',
            'status' => TurnStatus::class,
            'called_at' => 'datetime',
            'finished_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The code shown to patients and on the waiting room screen, e.g. "T-007".
     *
     * @return Attribute<string, never>
     */
    protected function code(): Attribute
    {
        return Attribute::get(fn (): string => 'T-'.str_pad((string) $this->number, 3, '0', STR_PAD_LEFT));
    }

    /**
     * Waiting turns of the same doctor that were issued before this one.
     */
    public function peopleAhead(): int
    {
        if ($this->status !== TurnStatus::Waiting) {
            return 0;
        }

        return self::query()
            ->whereDate('turn_date', $this->turn_date)
            ->where('doctor_id', $this->doctor_id)
            ->where('status', TurnStatus::Waiting)
            ->where('number', '<', $this->number)
            ->count();
    }

    /**
     * @param  Builder<Turn>  $query
     */
    public function scopeToday(Builder $query): void
    {
        $query->whereDate('turn_date', today());
    }

    /**
     * @param  Builder<Turn>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', TurnStatus::active());
    }

    /**
     * Limit the query to the turns the user manages: the whole queue for staff,
     * only their own queue for doctors.
     *
     * @param  Builder<Turn>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->role === UserRole::Doctor) {
            $query->where('doctor_id', $user->doctor?->id);
        }
    }
}
