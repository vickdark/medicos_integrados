<?php

namespace App\Exports;

use App\Models\Patient;

/**
 * Patient directory. Clinical data is intentionally excluded.
 */
class PatientsExport extends TableExport
{
    public function title(): string
    {
        return 'Pacientes';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Documento', 'Apellidos', 'Nombres', 'Edad', 'Sexo', 'Grupo sanguíneo', 'Teléfono', 'Correo', 'Dirección', 'Cuenta en portal'];
    }

    /**
     * @return iterable<int, list<string|int|float|null>>
     */
    public function rows(): iterable
    {
        foreach ($this->query->lazy(500) as $patient) {
            /** @var Patient $patient */
            yield [
                $patient->document_number,
                $patient->last_name,
                $patient->first_name,
                $patient->birth_date?->age,
                $patient->gender?->label(),
                $patient->blood_type,
                $patient->phone,
                $patient->email,
                $patient->address,
                $patient->user_id !== null ? 'Sí' : 'No',
            ];
        }
    }
}
