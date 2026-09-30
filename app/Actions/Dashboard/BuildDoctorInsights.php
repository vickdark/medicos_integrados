<?php

namespace App\Actions\Dashboard;

use App\Enums\AppointmentStatus;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Figures, agenda and recent activity for the doctor's own dashboard.
 */
class BuildDoctorInsights
{
    private const ACTIVE = [AppointmentStatus::Requested, AppointmentStatus::Confirmed];

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user, Request $request): array
    {
        $doctor = $user->doctor;

        if ($doctor === null) {
            return [];
        }

        $today = CarbonImmutable::today();
        $monthStart = $today->startOfMonth();
        $mine = fn (): Builder => Appointment::query()->where('doctor_id', $doctor->id);

        $consultationsThisMonth = $doctor->consultations()->where('consulted_at', '>=', $monthStart)->count();
        $consultationsPreviousMonth = $doctor->consultations()
            ->whereBetween('consulted_at', [$monthStart->subMonth(), $monthStart->subSecond()])
            ->count();

        $nextAppointment = $mine()
            ->whereIn('status', self::ACTIVE)
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->first();

        return [
            'kpis' => [
                'appointments_today' => $mine()->whereDate('scheduled_at', $today)->where('status', '!=', AppointmentStatus::Cancelled)->count(),
                'appointments_today_open' => $mine()->whereDate('scheduled_at', $today)->whereIn('status', self::ACTIVE)->count(),
                'requests' => $mine()->where('status', AppointmentStatus::Requested)->count(),
                'patients' => Patient::query()->treatedBy($doctor)->count(),
                'consultations_month' => $consultationsThisMonth,
                'consultations_delta' => $consultationsPreviousMonth > 0
                    ? round((($consultationsThisMonth - $consultationsPreviousMonth) / $consultationsPreviousMonth) * 100)
                    : null,
            ],
            'next_appointment' => $nextAppointment
                ? $this->resolve($request, $nextAppointment->load(['patient', 'doctor.user', 'doctor.specialty', 'consultation']))
                : null,
            'today_agenda' => $this->list(
                $request,
                $mine()->whereDate('scheduled_at', $today)->where('status', '!=', AppointmentStatus::Cancelled)->orderBy('scheduled_at'),
                12,
            ),
            'pending_requests' => $this->list(
                $request,
                $mine()->where('status', AppointmentStatus::Requested)->orderBy('scheduled_at'),
                6,
            ),
            'week_load' => $this->weekLoad($doctor->id, $today),
            'consultations_by_month' => $this->consultationsByMonth($doctor->id, $today),
            'recent_consultations' => Consultation::query()
                ->where('doctor_id', $doctor->id)
                ->with('patient')
                ->latest('consulted_at')
                ->limit(5)
                ->get()
                ->map(fn (Consultation $consultation): array => [
                    'id' => $consultation->id,
                    'patient' => $consultation->patient->full_name,
                    'patient_id' => $consultation->patient_id,
                    'reason' => $consultation->reason,
                    'consulted_at' => $consultation->consulted_at->toIso8601String(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolve(Request $request, Appointment $appointment): array
    {
        return (new AppointmentResource($appointment))->resolve($request);
    }

    /**
     * @param  Builder<Appointment>  $query
     * @return list<array<string, mixed>>
     */
    private function list(Request $request, Builder $query, int $limit): array
    {
        return $query
            ->with(['patient', 'doctor.user', 'doctor.specialty', 'consultation'])
            ->limit($limit)
            ->get()
            ->map(fn (Appointment $appointment): array => $this->resolve($request, $appointment))
            ->all();
    }

    /**
     * Appointments of the doctor for today and the next six days.
     *
     * @return list<array{label: string, date: string, value: int, today: bool}>
     */
    private function weekLoad(int $doctorId, CarbonImmutable $today): array
    {
        $counts = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('status', self::ACTIVE)
            ->whereBetween('scheduled_at', [$today, $today->addDays(6)->endOfDay()])
            ->get(['scheduled_at'])
            ->countBy(fn (Appointment $appointment): string => $appointment->scheduled_at->toDateString());

        return collect(range(0, 6))
            ->map(function (int $offset) use ($today, $counts): array {
                $day = $today->addDays($offset);

                return [
                    'label' => $day->translatedFormat('D'),
                    'date' => $day->toDateString(),
                    'value' => (int) $counts->get($day->toDateString(), 0),
                    'today' => $offset === 0,
                ];
            })
            ->all();
    }

    /**
     * Consultations of the last six months, oldest first.
     *
     * @return list<array{label: string, value: int, current: bool}>
     */
    private function consultationsByMonth(int $doctorId, CarbonImmutable $today): array
    {
        $start = $today->startOfMonth()->subMonths(5);

        $counts = Consultation::query()
            ->where('doctor_id', $doctorId)
            ->where('consulted_at', '>=', $start)
            ->get(['consulted_at'])
            ->countBy(fn (Consultation $consultation): string => $consultation->consulted_at->format('Y-m'));

        return collect(range(0, 5))
            ->map(function (int $offset) use ($start, $counts, $today): array {
                $month = $start->addMonths($offset);

                return [
                    'label' => $month->translatedFormat('M'),
                    'value' => (int) $counts->get($month->format('Y-m'), 0),
                    'current' => $month->isSameMonth($today),
                ];
            })
            ->all();
    }
}
