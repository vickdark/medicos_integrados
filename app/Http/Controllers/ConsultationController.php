<?php

namespace App\Http\Controllers;

use App\Actions\Payments\OpenAppointmentCharge;
use App\Enums\AppointmentStatus;
use App\Enums\AuditAction;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Resources\ConsultationResource;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Medication;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationController extends Controller
{
    /**
     * Show the form to register a consultation for the patient.
     */
    public function create(Request $request, Patient $patient): Response
    {
        Gate::authorize('create', [Consultation::class, $patient]);

        $pendingAppointments = $patient->appointments()
            ->whereBelongsTo($request->user()->doctor)
            ->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])
            ->whereDoesntHave('consultation')
            ->orderBy('scheduled_at')
            ->get(['id', 'scheduled_at', 'reason']);

        return Inertia::render('consultations/Create', [
            'patient' => [
                'id' => $patient->id,
                'full_name' => $patient->full_name,
                'allergies' => $patient->allergies,
                'chronic_conditions' => $patient->chronic_conditions,
            ],
            'appointments' => $pendingAppointments->map(fn ($appointment): array => [
                'id' => $appointment->id,
                'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
                'reason' => $appointment->reason,
            ]),
            'selectedAppointmentId' => $request->integer('appointment_id') ?: null,
            'medications' => Medication::query()
                ->orderBy('name')
                ->orderBy('concentration')
                ->get()
                ->map(fn (Medication $medication): array => [
                    'id' => $medication->id,
                    'name' => $medication->name,
                    'presentation' => $medication->presentation,
                    'concentration' => $medication->concentration,
                    'label' => $medication->label,
                ]),
        ]);
    }

    /**
     * Store the consultation and its prescriptions.
     */
    public function store(StoreConsultationRequest $request, Patient $patient, OpenAppointmentCharge $charge): RedirectResponse
    {
        $consultation = DB::transaction(function () use ($request, $patient, $charge): Consultation {
            $consultation = $patient->consultations()->create([
                ...$request->safe()->except('prescriptions'),
                'doctor_id' => $request->user()->doctor->id,
                'consulted_at' => now(),
            ]);

            $consultation->prescriptions()->createMany($request->validated('prescriptions', []));

            if ($consultation->appointment) {
                $consultation->appointment->update(['status' => AppointmentStatus::Completed]);
                $charge->open($consultation->appointment);
            }

            AuditLog::record(AuditAction::Created, $consultation, 'Registró una consulta en la historia clínica', $patient);

            return $consultation;
        });

        return to_route('consultations.show', $consultation)->with('success', 'Consulta registrada en la historia clínica.');
    }

    /**
     * Display the consultation detail.
     */
    public function show(Request $request, Consultation $consultation): Response
    {
        Gate::authorize('view', $consultation);

        $consultation->load(['patient', 'doctor.user', 'doctor.specialty', 'prescriptions', 'attachments']);

        if ($request->user()->isStaff()) {
            AuditLog::record(AuditAction::Viewed, $consultation, 'Consultó el detalle de una consulta', $consultation->patient);
        }

        return Inertia::render('consultations/Show', [
            'consultation' => new ConsultationResource($consultation),
            'can' => [
                'manage_attachments' => $request->user()->can('manageAttachments', $consultation),
                'download_prescription' => $consultation->prescriptions->isNotEmpty()
                    && $request->user()->can('downloadPrescription', $consultation),
                'email_prescription' => $consultation->prescriptions->isNotEmpty()
                    && $request->user()->can('emailPrescription', $consultation),
            ],
            'patientEmail' => $request->user()->can('emailPrescription', $consultation)
                ? ($consultation->patient->email ?: $consultation->patient->user?->email)
                : null,
        ]);
    }
}
