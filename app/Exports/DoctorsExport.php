<?php

namespace App\Exports;

use App\Models\Doctor;

class DoctorsExport extends TableExport
{
    public function title(): string
    {
        return 'Médicos';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Nombre', 'Correo', 'Especialidad', 'Colegiatura', 'Teléfono', 'Tarifa'];
    }

    /**
     * @return list<int>
     */
    public function moneyColumns(): array
    {
        return [5];
    }

    /**
     * @return iterable<int, list<string|int|float|null>>
     */
    public function rows(): iterable
    {
        foreach ($this->query->with(['user', 'specialty'])->lazy(500) as $doctor) {
            /** @var Doctor $doctor */
            yield [
                $doctor->user->name,
                $doctor->user->email,
                $doctor->specialty->name,
                $doctor->license_number,
                $doctor->phone,
                (float) $doctor->consultation_fee,
            ];
        }
    }
}
