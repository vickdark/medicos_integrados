<?php

namespace App\Exports;

use App\Enums\AuditAction;
use App\Models\AuditLog;

class AuditLogsExport extends TableExport
{
    public function title(): string
    {
        return 'Auditoría de accesos';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Fecha', 'Usuario', 'Rol', 'Acción', 'Descripción', 'Paciente', 'IP'];
    }

    /**
     * @return iterable<int, list<string|int|float|null>>
     */
    public function rows(): iterable
    {
        foreach ($this->query->with(['user', 'patient'])->lazy(500) as $log) {
            /** @var AuditLog $log */
            yield [
                $log->created_at?->format('d/m/Y g:i A'),
                $log->user?->name ?? 'Usuario eliminado',
                $log->user?->role->label(),
                $log->action->label(),
                $log->description,
                $log->patient?->full_name,
                $log->ip_address,
            ];
        }
    }

    /**
     * @return list<string>
     */
    public function filterSummary(): array
    {
        $action = AuditAction::tryFrom((string) ($this->filters['action'] ?? ''));

        return [
            ...parent::filterSummary(),
            ...($action ? ["Acción: {$action->label()}"] : []),
        ];
    }
}
