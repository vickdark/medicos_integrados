<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Privacy\BuildPatientDataExport;
use App\Enums\AuditAction;
use App\Enums\DataRequestType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataSubjectRequestRequest;
use App\Models\AuditLog;
use App\Models\DataSubjectRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonalDataController extends Controller
{
    /**
     * Show the patient's rights over their personal data and their requests.
     */
    public function edit(Request $request): Response
    {
        $patient = $request->user()->patient;

        abort_if($patient === null, 404);

        return Inertia::render('settings/PersonalData', [
            'policy' => [
                'version' => $request->user()->privacy_policy_version,
                'accepted_at' => $request->user()->privacy_accepted_at?->toIso8601String(),
            ],
            'types' => collect(DataRequestType::cases())
                ->map(fn (DataRequestType $type): array => [...$type->toOption(), 'days' => $type->responseBusinessDays()])
                ->all(),
            'requests' => $patient->dataSubjectRequests()
                ->latest()
                ->latest('id')
                ->get()
                ->map(fn (DataSubjectRequest $dataRequest): array => [
                    'id' => $dataRequest->id,
                    'type' => $dataRequest->type->toOption(),
                    'details' => $dataRequest->details,
                    'status' => $dataRequest->status->toOption(),
                    'is_overdue' => $dataRequest->isOverdue(),
                    'due_at' => $dataRequest->due_at->toDateString(),
                    'response' => $dataRequest->response,
                    'responded_at' => $dataRequest->responded_at?->toIso8601String(),
                    'created_at' => $dataRequest->created_at?->toIso8601String(),
                ])
                ->all(),
        ]);
    }

    /**
     * Register a request about the patient's personal data, with its legal deadline.
     */
    public function store(StoreDataSubjectRequestRequest $request): RedirectResponse
    {
        $user = $request->user();

        $dataRequest = DataSubjectRequest::open(
            $user->patient,
            $user,
            $request->enum('type', DataRequestType::class),
            $request->validated('details'),
        );

        AuditLog::record(AuditAction::Created, $dataRequest, "Registró la solicitud sobre sus datos personales «{$dataRequest->type->label()}»", $user->patient);

        return back()->with('success', 'Recibimos tu solicitud. Te responderemos antes del '.$dataRequest->due_at->translatedFormat('j \\d\\e F \\d\\e Y').'.');
    }

    /**
     * Download a copy of the personal data the clinic holds about the patient.
     */
    public function download(Request $request, BuildPatientDataExport $export): StreamedResponse
    {
        $patient = $request->user()->patient;

        abort_if($patient === null, 404);

        AuditLog::record(AuditAction::Exported, $patient, 'Descargó una copia de sus datos personales');

        return response()->streamDownload(
            function () use ($export, $patient): void {
                echo json_encode($export->build($patient), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            },
            'mis-datos-'.now()->format('Ymd').'.json',
            ['Content-Type' => 'application/json'],
        );
    }
}
