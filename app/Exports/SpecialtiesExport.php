<?php

namespace App\Exports;

use App\Models\Specialty;

class SpecialtiesExport extends TableExport
{
    public function title(): string
    {
        return 'Especialidades';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Especialidad', 'Descripción', 'Médicos'];
    }

    /**
     * @return iterable<int, list<string|int|float|null>>
     */
    public function rows(): iterable
    {
        foreach ($this->query->lazy(500) as $specialty) {
            /** @var Specialty $specialty */
            yield [
                $specialty->name,
                $specialty->description,
                (int) $specialty->doctors_count,
            ];
        }
    }
}
