<?php

namespace App\Exports;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;

class AppointmentsExport extends TableExport
{
    public function title(): string
    {
        return 'Citas';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Fecha', 'Hora', 'Paciente', 'Documento', 'Médico', 'Especialidad', 'Motivo', 'Estado'];
    }

    /**
     * @return iterable<int, list<string|int|float|null>>
     */
    public function rows(): iterable
    {
        foreach ($this->query->with(['patient', 'doctor.user', 'doctor.specialty'])->lazy(500) as $appointment) {
            /** @var Appointment $appointment */
            yield [
                $appointment->scheduled_at->format('d/m/Y'),
                $appointment->scheduled_at->format('g:i A'),
                $appointment->patient->full_name,
                $appointment->patient->document_number,
                $appointment->doctor->user->name,
                $appointment->doctor->specialty->name,
                $appointment->reason,
                $appointment->status->label(),
            ];
        }
    }

    /**
     * @return list<string>
     */
    public function filterSummary(): array
    {
        $status = AppointmentStatus::tryFrom((string) ($this->filters['status'] ?? ''));

        return [
            ...parent::filterSummary(),
            ...($status ? ["Estado: {$status->label()}"] : []),
        ];
    }
}
