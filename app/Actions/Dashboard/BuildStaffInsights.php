<?php

namespace App\Actions\Dashboard;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Figures, charts and work queues for the administrator and reception dashboards.
 */
class BuildStaffInsights
{
    private const ACTIVE = [AppointmentStatus::Requested, AppointmentStatus::Confirmed];

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user, Request $request): array
    {
        $today = CarbonImmutable::today();
        $monthStart = $today->startOfMonth();
        $previousMonthStart = $monthStart->subMonth();

        $incomeThisMonth = $this->paidBetween($monthStart, $today->endOfDay());
        $incomePreviousMonth = $this->paidBetween($previousMonthStart, $monthStart->subSecond());
        $pending = Payment::query()->where('status', PaymentStatus::Pending);

        $todayAppointments = Appointment::query()
            ->whereDate('scheduled_at', $today)
            ->where('status', '!=', AppointmentStatus::Cancelled);

        return [
            'kpis' => [
                'patients' => Patient::query()->count(),
                'patients_new_month' => Patient::query()->where('created_at', '>=', $monthStart)->count(),
                'appointments_today' => (clone $todayAppointments)->count(),
                'appointments_today_open' => (clone $todayAppointments)->whereIn('status', self::ACTIVE)->count(),
                'requests' => Appointment::query()->where('status', AppointmentStatus::Requested)->count(),
                'income_month' => $incomeThisMonth,
                'income_delta' => $incomePreviousMonth > 0
                    ? round((($incomeThisMonth - $incomePreviousMonth) / $incomePreviousMonth) * 100)
                    : null,
                'pending_amount' => (float) (clone $pending)->sum('amount'),
                'pending_count' => (clone $pending)->count(),
            ],
            'income_by_month' => $this->incomeByMonth($today),
            'appointments_by_day' => $this->appointmentsByDay($today),
            'today_agenda' => $this->appointmentList(
                $request,
                Appointment::query()
                    ->whereDate('scheduled_at', $today)
                    ->where('status', '!=', AppointmentStatus::Cancelled)
                    ->orderBy('scheduled_at'),
                12,
            ),
            'pending_requests' => $this->appointmentList(
                $request,
                Appointment::query()->where('status', AppointmentStatus::Requested)->orderBy('scheduled_at'),
                6,
            ),
            'pending_payments' => Payment::query()
                ->where('status', PaymentStatus::Pending)
                ->with(['patient', 'appointment'])
                ->oldest()
                ->limit(6)
                ->get()
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'patient' => $payment->patient->full_name,
                    'concept' => $payment->concept,
                    'amount' => $payment->amount,
                    'appointment_at' => $payment->appointment?->scheduled_at->toIso8601String(),
                ])
                ->all(),
            'top_doctors' => $user->role === UserRole::Admin ? $this->topDoctors($monthStart) : [],
        ];
    }

    /**
     * Total paid inside the period.
     */
    private function paidBetween(CarbonImmutable $from, CarbonImmutable $to): float
    {
        return (float) Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');
    }

    /**
     * Income of the last six months, oldest first. The current month is flagged.
     *
     * @return list<array{label: string, value: float, current: bool}>
     */
    private function incomeByMonth(CarbonImmutable $today): array
    {
        $start = $today->startOfMonth()->subMonths(5);

        $totals = Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->where('paid_at', '>=', $start->toDateString())
            ->get(['paid_at', 'amount'])
            ->groupBy(fn (Payment $payment): string => $payment->paid_at->format('Y-m'))
            ->map(fn ($payments): float => (float) $payments->sum('amount'));

        return collect(range(0, 5))
            ->map(function (int $offset) use ($start, $totals, $today): array {
                $month = $start->addMonths($offset);

                return [
                    'label' => $month->translatedFormat('M'),
                    'value' => round((float) $totals->get($month->format('Y-m'), 0), 2),
                    'current' => $month->isSameMonth($today),
                ];
            })
            ->all();
    }

    /**
     * Active or completed appointments of the last 14 days plus the next 6, so the
     * chart shows the recent load and what is coming.
     *
     * @return list<array{label: string, date: string, value: int, today: bool}>
     */
    private function appointmentsByDay(CarbonImmutable $today): array
    {
        $start = $today->subDays(7);
        $end = $today->addDays(6);

        $counts = Appointment::query()
            ->where('status', '!=', AppointmentStatus::Cancelled)
            ->whereBetween('scheduled_at', [$start, $end->endOfDay()])
            ->get(['scheduled_at'])
            ->countBy(fn (Appointment $appointment): string => $appointment->scheduled_at->toDateString());

        return collect(range(0, 13))
            ->map(function (int $offset) use ($start, $counts, $today): array {
                $day = $start->addDays($offset);

                return [
                    'label' => $day->format('j'),
                    'date' => $day->toDateString(),
                    'value' => (int) $counts->get($day->toDateString(), 0),
                    'today' => $day->isSameDay($today),
                ];
            })
            ->all();
    }

    /**
     * @param  Builder<Appointment>  $query
     * @return list<array<string, mixed>>
     */
    private function appointmentList(Request $request, Builder $query, int $limit): array
    {
        return $query
            ->with(['patient', 'doctor.user', 'doctor.specialty', 'consultation'])
            ->withPaymentFlags()
            ->limit($limit)
            ->get()
            ->map(fn (Appointment $appointment): array => (new AppointmentResource($appointment))->resolve($request))
            ->all();
    }

    /**
     * Doctors with the most appointments this month.
     *
     * @return list<array{id: int, name: string, specialty: string, appointments: int}>
     */
    private function topDoctors(CarbonImmutable $monthStart): array
    {
        return Doctor::query()
            ->with(['user', 'specialty'])
            ->withCount(['appointments as month_appointments' => fn (Builder $query) => $query
                ->where('status', '!=', AppointmentStatus::Cancelled)
                ->where('scheduled_at', '>=', $monthStart)])
            ->get()
            ->sortByDesc('month_appointments')
            ->take(5)
            ->filter(fn (Doctor $doctor): bool => $doctor->month_appointments > 0)
            ->map(fn (Doctor $doctor): array => [
                'id' => $doctor->id,
                'name' => $doctor->user->name,
                'specialty' => $doctor->specialty->name,
                'appointments' => (int) $doctor->month_appointments,
            ])
            ->values()
            ->all();
    }
}
