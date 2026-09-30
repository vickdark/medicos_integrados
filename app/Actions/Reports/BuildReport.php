<?php

namespace App\Actions\Reports;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReportGroup;
use App\Enums\ReportType;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Aggregates appointments, income or consultations of a period by doctor or by
 * specialty. The same result feeds the report page and its exports.
 *
 * @phpstan-type Report array{
 *     title: string,
 *     headings: list<string>,
 *     rows: list<list<string|int|float|null>>,
 *     totals: list<string|int|float|null>,
 *     money_columns: list<int>
 * }
 */
class BuildReport
{
    /**
     * @return Report
     */
    public function handle(
        ReportType $type,
        ReportGroup $group,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $doctorId = null,
        ?int $specialtyId = null,
    ): array {
        $period = [$from->startOfDay(), $to->endOfDay()];

        $report = match ($type) {
            ReportType::Appointments => $this->appointments($group, $period, $doctorId, $specialtyId),
            ReportType::Income => $this->income($group, $from, $to, $period, $doctorId, $specialtyId),
            ReportType::Consultations => $this->consultations($group, $period, $doctorId, $specialtyId),
        };

        $byDoctor = $group === ReportGroup::Doctor;
        $metricStart = $byDoctor ? 2 : 1;

        $rows = collect($report['rows'])
            ->sortByDesc(fn (array $row): int|float => $row[$metricStart + $report['sort']])
            ->values()
            ->map(fn (array $row): array => array_values($row))
            ->all();

        return [
            'title' => "Reporte de {$this->lowerLabel($type)} {$this->lowerLabel($group)}",
            'headings' => [
                $byDoctor ? 'Médico' : 'Especialidad',
                ...($byDoctor ? ['Especialidad'] : []),
                ...$report['metrics'],
            ],
            'rows' => $rows,
            'totals' => $this->totals($rows, $metricStart, $byDoctor),
            'money_columns' => array_map(fn (int $index): int => $index + $metricStart, $report['money']),
        ];
    }

    /**
     * @param  array{0: CarbonImmutable, 1: CarbonImmutable}  $period
     * @return array{metrics: list<string>, money: list<int>, sort: int, rows: list<list<string|int|float|null>>}
     */
    private function appointments(ReportGroup $group, array $period, ?int $doctorId, ?int $specialtyId): array
    {
        $count = fn (AppointmentStatus $status): string => "sum(case when appointments.status = '{$status->value}' then 1 else 0 end)";

        $query = Appointment::query()
            ->whereBetween('appointments.scheduled_at', $period);

        $rows = $this->grouped($query, 'appointments.doctor_id', $group, $doctorId, $specialtyId, false)
            ->selectRaw('count(*) as total')
            ->selectRaw($count(AppointmentStatus::Requested).' as requested')
            ->selectRaw($count(AppointmentStatus::Confirmed).' as confirmed')
            ->selectRaw($count(AppointmentStatus::Completed).' as completed')
            ->selectRaw($count(AppointmentStatus::Cancelled).' as cancelled')
            ->get()
            ->map(fn (Model $row): array => [
                $row->getAttribute('label'),
                ...($group === ReportGroup::Doctor ? [$row->getAttribute('detail')] : []),
                (int) $row->getAttribute('total'),
                (int) $row->getAttribute('requested'),
                (int) $row->getAttribute('confirmed'),
                (int) $row->getAttribute('completed'),
                (int) $row->getAttribute('cancelled'),
            ])
            ->all();

        return [
            'metrics' => ['Citas', 'Solicitadas', 'Confirmadas', 'Completadas', 'Canceladas'],
            'money' => [],
            'sort' => 0,
            'rows' => $rows,
        ];
    }

    /**
     * Paid payments count by their payment date and pending ones by the date they
     * were opened. Voided payments are left out.
     *
     * @param  array{0: CarbonImmutable, 1: CarbonImmutable}  $period
     * @return array{metrics: list<string>, money: list<int>, sort: int, rows: list<list<string|int|float|null>>}
     */
    private function income(ReportGroup $group, CarbonImmutable $from, CarbonImmutable $to, array $period, ?int $doctorId, ?int $specialtyId): array
    {
        $query = Payment::query()
            ->leftJoin('appointments', 'appointments.id', '=', 'payments.appointment_id')
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('payments.status', PaymentStatus::Paid)
                    ->whereBetween('payments.paid_at', [$from->toDateString(), $to->toDateString()]))
                ->orWhere(fn (Builder $query) => $query
                    ->where('payments.status', PaymentStatus::Pending)
                    ->whereBetween('payments.created_at', $period)));

        $paid = PaymentStatus::Paid->value;
        $pending = PaymentStatus::Pending->value;

        $rows = $this->grouped($query, 'appointments.doctor_id', $group, $doctorId, $specialtyId, true)
            ->selectRaw("sum(case when payments.status = '{$paid}' then 1 else 0 end) as paid_count")
            ->selectRaw("sum(case when payments.status = '{$paid}' then payments.amount else 0 end) as paid_amount")
            ->selectRaw("sum(case when payments.status = '{$pending}' then 1 else 0 end) as pending_count")
            ->selectRaw("sum(case when payments.status = '{$pending}' then payments.amount else 0 end) as pending_amount")
            ->get()
            ->map(fn (Model $row): array => [
                $row->getAttribute('label'),
                ...($group === ReportGroup::Doctor ? [$row->getAttribute('detail')] : []),
                (int) $row->getAttribute('paid_count'),
                (float) $row->getAttribute('paid_amount'),
                (int) $row->getAttribute('pending_count'),
                (float) $row->getAttribute('pending_amount'),
            ])
            ->all();

        return [
            'metrics' => ['Pagos cobrados', 'Monto cobrado', 'Pagos pendientes', 'Monto pendiente'],
            'money' => [1, 3],
            'sort' => 1,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array{0: CarbonImmutable, 1: CarbonImmutable}  $period
     * @return array{metrics: list<string>, money: list<int>, sort: int, rows: list<list<string|int|float|null>>}
     */
    private function consultations(ReportGroup $group, array $period, ?int $doctorId, ?int $specialtyId): array
    {
        $query = Consultation::query()
            ->whereBetween('consultations.consulted_at', $period);

        $rows = $this->grouped($query, 'consultations.doctor_id', $group, $doctorId, $specialtyId, false)
            ->selectRaw('count(*) as total')
            ->selectRaw('count(distinct consultations.patient_id) as patients')
            ->get()
            ->map(fn (Model $row): array => [
                $row->getAttribute('label'),
                ...($group === ReportGroup::Doctor ? [$row->getAttribute('detail')] : []),
                (int) $row->getAttribute('total'),
                (int) $row->getAttribute('patients'),
            ])
            ->all();

        return [
            'metrics' => ['Consultas', 'Pacientes atendidos'],
            'money' => [],
            'sort' => 0,
            'rows' => $rows,
        ];
    }

    /**
     * Join the doctor, their user and specialty, apply the doctor and specialty
     * filters and group the query by doctor or by specialty. Patients' payments
     * without an appointment have no doctor and fall in an "unassigned" group.
     *
     * @param  Builder<covariant Model>  $query
     * @return Builder<covariant Model>
     */
    private function grouped(Builder $query, string $doctorColumn, ReportGroup $group, ?int $doctorId, ?int $specialtyId, bool $keepUnassigned): Builder
    {
        $join = $keepUnassigned ? 'leftJoin' : 'join';

        $query
            ->{$join}('doctors', 'doctors.id', '=', $doctorColumn)
            ->{$join}('users', 'users.id', '=', 'doctors.user_id')
            ->{$join}('specialties', 'specialties.id', '=', 'doctors.specialty_id')
            ->when($doctorId, fn (Builder $query) => $query->where('doctors.id', $doctorId))
            ->when($specialtyId, fn (Builder $query) => $query->where('doctors.specialty_id', $specialtyId));

        if ($group === ReportGroup::Doctor) {
            return $query
                ->selectRaw("coalesce(users.name, 'Sin médico (pago sin cita)') as label")
                ->selectRaw('specialties.name as detail')
                ->groupBy('doctors.id', 'users.name', 'specialties.name');
        }

        return $query
            ->selectRaw("coalesce(specialties.name, 'Sin especialidad (pago sin cita)') as label")
            ->groupBy('specialties.id', 'specialties.name');
    }

    /**
     * @param  list<list<string|int|float|null>>  $rows
     * @return list<string|int|float|null>
     */
    private function totals(array $rows, int $metricStart, bool $byDoctor): array
    {
        if ($rows === []) {
            return [];
        }

        $columns = count($rows[0]);
        $totals = ['Total', ...($byDoctor ? [null] : [])];

        for ($column = $metricStart; $column < $columns; $column++) {
            $totals[] = array_sum(array_column($rows, $column));
        }

        return $totals;
    }

    private function lowerLabel(ReportType|ReportGroup $case): string
    {
        return mb_strtolower($case->label());
    }
}
