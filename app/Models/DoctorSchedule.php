<?php

namespace App\Models;

use Database\Factories\DoctorScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends Model
{
    /** @use HasFactory<DoctorScheduleFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'starts_at',
        'ends_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    /**
     * Spanish day names indexed by Carbon's day of week (0 = Sunday).
     *
     * @var array<int, string>
     */
    public const DAY_NAMES = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    /**
     * Get the starting time as HH:MM regardless of how the database returns it.
     */
    public function startTime(): string
    {
        return substr((string) $this->starts_at, 0, 5);
    }

    /**
     * Get the ending time as HH:MM regardless of how the database returns it.
     */
    public function endTime(): string
    {
        return substr((string) $this->ends_at, 0, 5);
    }

    /**
     * Get a readable summary such as "Lunes 08:00 – 12:00".
     */
    public function summary(): string
    {
        return self::DAY_NAMES[$this->day_of_week].' '.$this->startTime().' – '.$this->endTime();
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
