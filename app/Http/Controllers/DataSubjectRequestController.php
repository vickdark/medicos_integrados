<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\DataRequestStatus;
use App\Http\Requests\AnswerDataSubjectRequestRequest;
use App\Http\Requests\TableQueryRequest;
use App\Models\AuditLog;
use App\Models\DataSubjectRequest;
use App\Notifications\DataRequestAnswered;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DataSubjectRequestController extends Controller
{
    /**
     * List the personal data requests of the patients, the closest deadline first.
     */
    public function index(TableQueryRequest $request): Response
    {
        Gate::authorize('viewAny', DataSubjectRequest::class);

        $term = $request->searchTerm();
        $status = DataRequestStatus::tryFrom($request->string('status')->toString());

        $requests = DataSubjectRequest::query()
            ->with('patient')
            ->when($term, fn (Builder $query) => $query->whereHas('patient', fn (Builder $query) => $query->search($term)))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->orderByRaw("status = 'pending' desc")
            ->orderBy('due_at')
            ->orderBy('id')
            ->paginate(self::TABLE_PAGE_SIZE)
            ->withQueryString()
            ->through(fn (DataSubjectRequest $dataRequest): array => [
                'id' => $dataRequest->id,
                'type' => $dataRequest->type->toOption(),
                'details' => $dataRequest->details,
                'status' => $dataRequest->status->toOption(),
                'is_overdue' => $dataRequest->isOverdue(),
                'due_at' => $dataRequest->due_at->toDateString(),
                'response' => $dataRequest->response,
                'responded_at' => $dataRequest->responded_at?->toIso8601String(),
                'responded_by_name' => $dataRequest->responded_by_name,
                'created_at' => $dataRequest->created_at?->toIso8601String(),
                'patient' => [
                    'id' => $dataRequest->patient->id,
                    'full_name' => $dataRequest->patient->full_name,
                    'document_number' => $dataRequest->patient->document_number,
                ],
            ]);

        return Inertia::render('data-requests/Index', [
            'requests' => $requests,
            'filters' => $request->filters(),
            'statuses' => DataRequestStatus::options(),
            'pendingCount' => DataSubjectRequest::query()->pending()->count(),
        ]);
    }

    /**
     * Answer the request, accepting or rejecting it with the reason, and tell the patient.
     */
    public function answer(AnswerDataSubjectRequestRequest $request, DataSubjectRequest $dataRequest): RedirectResponse
    {
        $dataRequest->answer(
            DataRequestStatus::from($request->validated('status')),
            $request->validated('response'),
            $request->user(),
        );

        AuditLog::record(AuditAction::Updated, $dataRequest, "Respondió la solicitud de datos personales «{$dataRequest->type->label()}» ({$dataRequest->status->label()})", $dataRequest->patient);

        $dataRequest->patient->user?->notify(new DataRequestAnswered($dataRequest));

        return back()->with('success', 'Respuesta registrada.');
    }
}
