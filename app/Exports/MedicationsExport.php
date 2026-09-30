<?php

namespace App\Exports;

use App\Models\Medication;

class MedicationsExport extends TableExport
{
    public function title(): string
    {
        return 'Medicamentos';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Medicamento', 'Presentación', 'Concentración', 'Descripción'];
    }

    /**
     * @return iterable<int, list<string|int|float|null>>
     */
    public function rows(): iterable
    {
        foreach ($this->query->lazy(500) as $medication) {
            /** @var Medication $medication */
            yield [
                $medication->name,
                $medication->presentation,
                $medication->concentration,
                $medication->description,
            ];
        }
    }
}
