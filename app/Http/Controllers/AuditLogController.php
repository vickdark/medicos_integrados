<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Exports\AuditLogsExport;
use App\Exports\TableExporter;
use App\Http\Requests\ExportTableRequest;
use App\Http\Requests\TableQueryRequest;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuditLogController extends Controller
{
    /**
     * Display who accessed or changed clinical information.
     */
    public function index(TableQueryRequest $request): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        $logs = $this->filteredQuery($request)
            ->with(['user', 'patient'])
            ->paginate(self::TABLE_PAGE_SIZE)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => (new AuditLogResource($log))->resolve($request));

        return Inertia::render('audit/Index', [
            'logs' => $logs,
            'filters' => $request->filters(),
            'patient' => $request->filled('patient_id')
                ? Patient::query()->find($request->integer('patient_id'))?->only(['id', 'full_name'])
                : null,
            'actions' => AuditAction::options(),
        ]);
    }

    /**
     * Export the filtered audit trail to Excel or PDF.
     */
    public function export(ExportTableRequest $request, TableExporter $exporter): SymfonyResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        return $exporter->download(
            new AuditLogsExport($this->filteredQuery($request), $request->filters()),
            $request->exportFormat(),
        );
    }

    /**
     * Audit entries narrowed by the table filters, newest first.
     *
     * @return Builder<AuditLog>
     */
    private function filteredQuery(TableQueryRequest $request): Builder
    {
        $term = $request->searchTerm();
        $action = AuditAction::tryFrom($request->string('action')->toString());

        return AuditLog::query()
            ->when($term, fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
                $query->where('description', 'like', "%{$term}%")
                    ->orWhere('ip_address', 'like', "%{$term}%")
                    ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('patient', fn (Builder $query) => $query->search($term));
            }))
            ->when($action, fn (Builder $query) => $query->where('action', $action))
            ->when($request->filled('patient_id'), fn (Builder $query) => $query->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest('created_at')
            ->latest('id');
    }
}
