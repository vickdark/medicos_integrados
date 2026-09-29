<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    /**
     * Display the appointments visible to the user.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Appointment::class);

        $request->validate([
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
        ]);

        $appointments = Appointment::query()
            ->visibleTo($request->user())
            ->with(['patient', 'doctor.user', 'doctor.specialty', 'consultation'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest('scheduled_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Appointment $appointment): array => (new AppointmentResource($appointment))->resolve($request));

        return Inertia::render('appointments/Index', [
            'appointments' => $appointments,
            'filters' => ['status' => $request->string('status')->toString()],
            'statuses' => AppointmentStatus::options(),
            'can' => ['create' => $request->user()->can('create', Appointment::class)],
        ]);
    }

    /**
     * Show the form to schedule or request an appointment.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Appointment::class);

        $isStaff = $request->user()->isStaff();

        return Inertia::render('appointments/Create', [
            'doctors' => Doctor::query()
                ->with(['user', 'specialty', 'schedules' => fn ($query) => $query->orderBy('day_of_week')->orderBy('starts_at')])
                ->get()
                ->sortBy('user.name')
                ->values()
                ->map(fn (Doctor $doctor): array => [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                    'specialty' => $doctor->specialty->name,
                    'consultation_fee' => $doctor->consultation_fee,
                    'schedules' => $doctor->schedules->map(fn (DoctorSchedule $schedule): string => $schedule->summary()),
                ]),
            'patients' => $isStaff
                ? Patient::query()
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get(['id', 'first_name', 'last_name', 'document_number'])
                    ->map(fn (Patient $patient): array => [
                        'id' => $patient->id,
                        'full_name' => $patient->full_name,
                        'document_number' => $patient->document_number,
                    ])
                : [],
            'selectedPatientId' => $isStaff ? ($request->integer('patient_id') ?: null) : null,
            'isStaff' => $isStaff,
        ]);
    }

    /**
     * Store the new appointment.
     */
    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $user = $request->user();

        $appointment = Appointment::create([
            ...$request->validated(),
            'patient_id' => $user->isStaff() ? $request->integer('patient_id') : $user->patient->id,
            'status' => $user->isStaff() ? AppointmentStatus::Confirmed : AppointmentStatus::Requested,
            'created_by' => $user->id,
        ]);

        if ($user->isStaff()) {
            $appointment->patient->user?->notify(new AppointmentConfirmed($appointment));
        } else {
            Notification::send(
                User::query()->whereIn('role', [UserRole::Admin, UserRole::Receptionist])->get(),
                new AppointmentRequested($appointment),
            );
        }

        return to_route('appointments.index')->with('success', $user->isStaff()
            ? 'Cita agendada correctamente.'
            : 'Solicitud enviada. Te avisaremos cuando la clínica confirme tu cita.');
    }

    /**
     * Change the status of the appointment and notify the other party.
     */
    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): RedirectResponse
    {
        $appointment->update(['status' => $request->enum('status', AppointmentStatus::class)]);

        match ($appointment->status) {
            AppointmentStatus::Confirmed => $appointment->patient->user?->notify(new AppointmentConfirmed($appointment)),
            AppointmentStatus::Cancelled => $request->user()->isStaff()
                ? $appointment->patient->user?->notify(new AppointmentCancelled($appointment))
                : $appointment->doctor->user->notify(new AppointmentCancelled($appointment)),
            default => null,
        };

        return back()->with('success', "Cita marcada como {$appointment->status->label()}.");
    }
}
