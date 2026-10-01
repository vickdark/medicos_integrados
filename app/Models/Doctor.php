<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

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
        'slot_minutes',
        'photo_path',
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
            'slot_minutes' => 'integer',
        ];
    }

    /**
     * Lengths, in minutes, a doctor can give to each appointment.
     *
     * @var list<int>
     */
    public const SLOT_OPTIONS = [10, 15, 20, 30, 45, 60];

    /**
     * Default length of an appointment slot.
     */
    public const DEFAULT_SLOT_MINUTES = 30;

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
     * @return HasMany<Turn, $this>
     */
    public function turns(): HasMany
    {
        return $this->hasMany(Turn::class);
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
     * Weekly office hours grouped for display: consecutive days that share the
     * same time ranges are merged, such as "Lun – Vie · 8:00 AM – 12:00 PM".
     *
     * @return list<array{days: string, ranges: list<string>}>
     */
    public function weeklyScheduleSummary(): array
    {
        $shortNames = [0 => 'Dom', 1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb'];
        $byDay = $this->schedules->sortBy('starts_at')->groupBy('day_of_week');
        $groups = [];

        foreach ([1, 2, 3, 4, 5, 6, 0] as $day) {
            if (! $byDay->has($day)) {
                continue;
            }

            $ranges = $byDay[$day]->map(fn (DoctorSchedule $schedule): string => $schedule->rangeLabel())->all();
            $last = array_key_last($groups);

            if ($last !== null && $groups[$last]['ranges'] === $ranges && $groups[$last]['lastDay'] === (($day + 6) % 7)) {
                $groups[$last]['lastDay'] = $day;
                $groups[$last]['end'] = $shortNames[$day];

                continue;
            }

            $groups[] = ['start' => $shortNames[$day], 'end' => $shortNames[$day], 'lastDay' => $day, 'ranges' => $ranges];
        }

        return array_map(fn (array $group): array => [
            'days' => $group['start'] === $group['end'] ? $group['start'] : "{$group['start']} – {$group['end']}",
            'ranges' => $group['ranges'],
        ], $groups);
    }

    /**
     * URL of the doctor's photo, versioned so a new upload is not served from cache.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path
            ? route('doctors.photo', ['doctor' => $this->id, 'v' => $this->updated_at?->timestamp])
            : null;
    }

    /**
     * Whether the doctor has registered the image of their signature. Documents
     * issued without it are marked as not valid.
     */
    public function hasSignature(): bool
    {
        return $this->signature_path !== null;
    }

    /**
     * URL of the signature image, only reachable by the doctor and the administrator.
     */
    public function signatureUrl(): ?string
    {
        return $this->signature_path
            ? route('doctors.signature', ['doctor' => $this->id, 'v' => $this->updated_at?->timestamp])
            : null;
    }

    /**
     * The signature as a data URI, to embed it in the PDFs.
     */
    public function signatureDataUri(): ?string
    {
        if (! $this->signature_path || ! Storage::disk('local')->exists($this->signature_path)) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return 'data:'.($disk->mimeType($this->signature_path) ?: 'image/png').';base64,'.base64_encode((string) $disk->get($this->signature_path));
    }

    /**
     * Length in minutes of each appointment of this doctor.
     */
    public function slotLength(): int
    {
        return (int) ($this->slot_minutes ?: self::DEFAULT_SLOT_MINUTES);
    }

    /**
     * Determine whether another active appointment overlaps the slot that would
     * start at the given time.
     */
    public function hasConflictAt(CarbonInterface $dateTime, ?int $ignoreAppointmentId = null): bool
    {
        return $this->appointments()
            ->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])
            ->when($ignoreAppointmentId, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->where('scheduled_at', '>', $dateTime->copy()->subMinutes($this->slotLength()))
            ->where('scheduled_at', '<', $dateTime->copy()->addMinutes($this->slotLength()))
            ->exists();
    }

    /**
     * Office hours and taken start times for every day of the window. Doctors
     * without registered hours are open all day, like isAvailableAt() accepts.
     *
     * @return array{slot_minutes: int, has_schedule: bool, days: array<string, array{blocks: list<array{start: string, end: string}>, booked: list<string>}>}
     */
    public function availabilityBetween(CarbonInterface $from, CarbonInterface $to, ?int $ignoreAppointmentId = null): array
    {
        $schedules = $this->schedules()->get()->groupBy('day_of_week');

        $booked = $this->appointments()
            ->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])
            ->when($ignoreAppointmentId, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->whereBetween('scheduled_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->pluck('scheduled_at')
            ->groupBy(fn (CarbonInterface $date): string => $date->toDateString());

        $days = [];

        for ($day = CarbonImmutable::instance($from)->startOfDay(); $day->lte($to); $day = $day->addDay()) {
            $blocks = $schedules->isEmpty()
                ? [['start' => '07:00', 'end' => '20:00']]
                : $schedules->get($day->dayOfWeek, collect())
                    ->sortBy('starts_at')
                    ->map(fn (DoctorSchedule $schedule): array => ['start' => $schedule->startTime(), 'end' => $schedule->endTime()])
                    ->values()
                    ->all();

            $days[$day->toDateString()] = [
                'blocks' => $blocks,
                'booked' => $booked->get($day->toDateString(), collect())
                    ->map(fn (CarbonInterface $date): string => $date->format('H:i'))
                    ->sort()
                    ->values()
                    ->all(),
            ];
        }

        return [
            'slot_minutes' => $this->slotLength(),
            'has_schedule' => $schedules->isNotEmpty(),
            'days' => $days,
        ];
    }

    /**
     * Determine whether the doctor has an appointment or consultation with the given patient.
     */
    public function treats(Patient $patient): bool
    {
        return $this->appointments()->whereBelongsTo($patient)->exists()
            || $this->consultations()->whereBelongsTo($patient)->exists()
            || $this->turns()->whereBelongsTo($patient)->exists();
    }
}
