<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Display who accessed or changed clinical information.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        $request->validate([
            'action' => ['nullable', Rule::enum(AuditAction::class)],
            'patient_id' => ['nullable', 'integer'],
        ]);

        $logs = AuditLog::query()
            ->with(['user', 'patient'])
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->toString()))
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->integer('patient_id')))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => (new AuditLogResource($log))->resolve($request));

        return Inertia::render('audit/Index', [
            'logs' => $logs,
            'filters' => [
                'action' => $request->string('action')->toString(),
                'patient_id' => $request->integer('patient_id') ?: null,
            ],
            'patient' => $request->filled('patient_id')
                ? Patient::query()->find($request->integer('patient_id'))?->only(['id', 'full_name'])
                : null,
            'actions' => AuditAction::options(),
        ]);
    }
}
