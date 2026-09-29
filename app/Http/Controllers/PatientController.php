<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\Gender;
use App\Enums\UserRole;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\ConsultationResource;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PaymentResource;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    /**
     * Display the list of patients visible to the user.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Patient::class);

        $user = $request->user();

        $patients = Patient::query()
            ->when($user->role === UserRole::Doctor, fn ($query) => $query->treatedBy($user->doctor ?? new Doctor))
            ->search($request->string('search')->toString())
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Patient $patient): array => (new PatientResource($patient))->resolve($request));

        return Inertia::render('patients/Index', [
            'patients' => $patients,
            'filters' => ['search' => $request->string('search')->toString()],
            'can' => ['create' => $user->can('create', Patient::class)],
        ]);
    }

    /**
     * Show the form to register a patient.
     */
    public function create(): Response
    {
        Gate::authorize('create', Patient::class);

        return Inertia::render('patients/Create', $this->formOptions());
    }

    /**
     * Store a newly registered patient.
     */
    public function store(StorePatientRequest $request): RedirectResponse
    {
        $patient = Patient::create($request->validated());

        AuditLog::record(AuditAction::Created, $patient, 'Registró la ficha del paciente');

        return to_route('patients.show', $patient)->with('success', 'Paciente registrado correctamente.');
    }

    /**
     * Display the patient's profile and history.
     */
    public function show(Request $request, Patient $patient): Response
    {
        Gate::authorize('view', $patient);

        $user = $request->user();
        $canViewMedicalHistory = $user->can('viewMedicalHistory', $patient);
        $canViewPayments = $user->can('viewAny', Payment::class);

        if ($canViewMedicalHistory && $user->isStaff()) {
            AuditLog::record(AuditAction::Viewed, $patient, 'Consultó la historia clínica');
        }

        return Inertia::render('patients/Show', [
            'patient' => new PatientResource($patient),
            'appointments' => AppointmentResource::collection(
                $patient->appointments()
                    ->with(['patient', 'doctor.user', 'doctor.specialty', 'consultation'])
                    ->latest('scheduled_at')
                    ->limit(20)
                    ->get()
            ),
            'consultations' => $canViewMedicalHistory
                ? ConsultationResource::collection(
                    $patient->consultations()
                        ->with(['doctor.user', 'doctor.specialty', 'prescriptions'])
                        ->latest('consulted_at')
                        ->get()
                )
                : null,
            'payments' => $canViewPayments
                ? PaymentResource::collection($patient->payments()->latest('paid_at')->latest()->limit(20)->get())
                : null,
            'can' => [
                'update' => $user->can('update', $patient),
                'view_medical_history' => $canViewMedicalHistory,
                'create_consultation' => $user->can('create', [Consultation::class, $patient]),
                'create_appointment' => $user->isStaff() && $user->can('create', Appointment::class),
                'create_payment' => $user->can('create', Payment::class),
                'view_audit_trail' => $user->can('viewAuditTrail', $patient),
            ],
        ]);
    }

    /**
     * Show the form to edit the patient. Patients only get their contact details.
     */
    public function edit(Request $request, Patient $patient): Response
    {
        Gate::authorize('update', $patient);

        return Inertia::render('patients/Edit', [
            'patient' => new PatientResource($patient),
            'contactOnly' => $request->user()->hasRole(UserRole::Patient),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the patient's record.
     */
    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());

        if ($patient->wasChanged()) {
            AuditLog::record(
                AuditAction::Updated,
                $patient,
                'Modificó: '.implode(', ', array_keys(Arr::except($patient->getChanges(), ['updated_at']))),
            );
        }

        return to_route('patients.show', $patient)->with('success', 'Datos del paciente actualizados.');
    }

    /**
     * Select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'genders' => Gender::options(),
            'bloodTypes' => ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'],
        ];
    }
}
