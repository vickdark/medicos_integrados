<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'specialty_id',
        'license_number',
        'phone',
        'bio',
        'consultation_fee',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consultation_fee' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Specialty, $this>
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * @return HasMany<DoctorSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
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
     * Determine whether the date and time falls within the doctor's office hours.
     * Doctors without registered office hours are considered always available.
     */
    public function isAvailableAt(CarbonInterface $dateTime): bool
    {
        $schedules = $this->schedules()->get();

        if ($schedules->isEmpty()) {
            return true;
        }

        $time = $dateTime->format('H:i');

        return $schedules
            ->where('day_of_week', $dateTime->dayOfWeek)
            ->contains(fn (DoctorSchedule $schedule): bool => $schedule->startTime() <= $time && $time < $schedule->endTime());
    }

    /**
     * Determine whether the doctor has an appointment or consultation with the given patient.
     */
    public function treats(Patient $patient): bool
    {
        return $this->appointments()->whereBelongsTo($patient)->exists()
            || $this->consultations()->whereBelongsTo($patient)->exists();
    }
}
