<?php

namespace App\Exports;

use App\Enums\UserRole;
use App\Models\User;

class UsersExport extends TableExport
{
    public function title(): string
    {
        return 'Usuarios';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Nombre', 'Correo', 'Rol', 'Especialidad', 'Registro médico', 'Documento', 'Acceso', 'Fecha de alta'];
    }

    /**
     * @return iterable<int, list<string|int|float|null>>
     */
    public function rows(): iterable
    {
        foreach ($this->query->with(['doctor.specialty', 'patient'])->lazy(500) as $user) {
            /** @var User $user */
            yield [
                $user->name,
                $user->email,
                $user->role->label(),
                $user->doctor?->specialty->name,
                $user->doctor?->license_number,
                $user->patient?->document_number,
                $user->is_active ? 'Activo' : 'Inactivo',
                $user->created_at?->format('d/m/Y'),
            ];
        }
    }

    /**
     * @return list<string>
     */
    public function filterSummary(): array
    {
        $role = UserRole::tryFrom((string) ($this->filters['role'] ?? ''));
        $status = ($this->filters['status'] ?? '') === 'inactive' ? 'Inactivos' : (($this->filters['status'] ?? '') === 'active' ? 'Activos' : null);

        return [
            ...parent::filterSummary(),
            ...($role ? ["Rol: {$role->label()}"] : []),
            ...($status ? ["Acceso: {$status}"] : []),
        ];
    }
}
