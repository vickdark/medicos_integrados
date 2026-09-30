<?php

namespace App\Http\Controllers;

use App\Actions\Payments\OpenAppointmentCharge;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Exports\AppointmentsExport;
use App\Exports\TableExporter;
use App\Http\Requests\CalendarRangeRequest;
use App\Http\Requests\ExportTableRequest;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\TableQueryRequest;
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
use App\Notifications\AppointmentRescheduled;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AppointmentController extends Controller
{
    /**
     * Display the appointments visible to the user.
     */
    public function index(TableQueryRequest $request): Response
    {
        Gate::authorize('viewAny', Appointment::class);

        $appointments = $this->filteredQuery($request)
            ->with(['patient', 'doctor.user', 'doctor.specialty', 'consultation'])
            ->withPaymentFlags()
            ->paginate(self::TABLE_PAGE_SIZE)
            ->withQueryString()
            ->through(fn (Appointment $appointment): array => (new AppointmentResource($appointment))->resolve($request));

        return Inertia::render('appointments/Index', [
            'appointments' => $appointments,
            'filters' => $request->filters(),
            'statuses' => AppointmentStatus::options(),
            'can' => ['create' => $request->user()->can('create', Appointment::class)],
        ]);
    }

    /**
     * Appointments inside a date window, for the calendar views. It is not
     * paginated because the window is capped by the request.
     */
    public function calendar(CalendarRangeRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Appointment::class);

        $appointments = Appointment::query()
            ->visibleTo($request->user())
            ->whereBetween('scheduled_at', [$request->rangeStart(), $request->rangeEnd()])
            ->with(['patient', 'doctor.user', 'doctor.specialty', 'consultation'])
            ->withPaymentFlags()
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Appointment $appointment): array => (new AppointmentResource($appointment))->resolve($request));

        return response()->json(['data' => $appointments]);
    }

    /**
     * Export the filtered appointments to Excel or PDF.
     */
    public function export(ExportTableRequest $request, TableExporter $exporter): SymfonyResponse
    {
        Gate::authorize('viewAny', Appointment::class);

        return $exporter->download(
            new AppointmentsExport($this->filteredQuery($request), $request->filters()),
            $request->exportFormat(),
        );
    }

    /**
     * Appointments visible to the user, narrowed by the table filters.
     *
     * @return Builder<Appointment>
     */
    private function filteredQuery(TableQueryRequest $request): Builder
    {
        $term = $request->searchTerm();
        $status = AppointmentStatus::tryFrom($request->string('status')->toString());

        return Appointment::query()
            ->visibleTo($request->user())
            ->when($term, fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
                $query->where('reason', 'like', "%{$term}%")
                    ->orWhereHas('patient', fn (Builder $query) => $query->search($term))
                    ->orWhereHas('doctor.user', fn (Builder $query) => $query->where('name', 'like', "%{$term}%"));
            }))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('scheduled_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('scheduled_at', '<=', $request->date('to')))
            ->latest('scheduled_at');
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
                    'slot_minutes' => $doctor->slotLength(),
                    'schedule_summary' => $doctor->weeklyScheduleSummary(),
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
    public function store(StoreAppointmentRequest $request, OpenAppointmentCharge $charge): RedirectResponse
    {
        $user = $request->user();

        $appointment = Appointment::create([
            ...$request->validated(),
            'patient_id' => $user->isStaff() ? $request->integer('patient_id') : $user->patient->id,
            'status' => $user->isStaff() ? AppointmentStatus::Confirmed : AppointmentStatus::Requested,
            'created_by' => $user->id,
        ]);

        if ($user->isStaff()) {
            $charge->open($appointment);
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
     * Show the form to move the appointment to another date and time.
     */
    public function edit(Request $request, Appointment $appointment): Response
    {
        Gate::authorize('reschedule', $appointment);

        $appointment->load(['patient', 'doctor.user', 'doctor.specialty', 'doctor.schedules']);

        return Inertia::render('appointments/Reschedule', [
            'appointment' => (new AppointmentResource($appointment))->resolve($request),
            'schedules' => $appointment->doctor->schedules
                ->sortBy(['day_of_week', 'starts_at'])
                ->map(fn (DoctorSchedule $schedule): string => $schedule->summary())
                ->values(),
            'isStaff' => $request->user()->isStaff(),
        ]);
    }

    /**
     * Move the appointment to another date and time. A patient's change goes back
     * to the clinic for confirmation; the rest of the roles keep the status.
     */
    public function update(RescheduleAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();
        $previousDate = $appointment->scheduled_at->copy();
        $needsConfirmation = $user->role === UserRole::Patient;

        $appointment->update([
            'scheduled_at' => $request->date('scheduled_at'),
            'status' => $needsConfirmation ? AppointmentStatus::Requested : $appointment->status,
        ]);

        $notification = new AppointmentRescheduled($appointment, $previousDate, $needsConfirmation);

        if ($needsConfirmation) {
            Notification::send(
                User::query()->whereIn('role', [UserRole::Admin, UserRole::Receptionist])->get()->push($appointment->doctor->user),
                $notification,
            );
        } else {
            $appointment->patient->user?->notify($notification);
        }

        return to_route('appointments.index')->with('success', $needsConfirmation
            ? 'Solicitaste el cambio de fecha. La clínica lo confirmará pronto.'
            : 'Cita reprogramada correctamente.');
    }

    /**
     * Change the status of the appointment and notify the other party.
     */
    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment, OpenAppointmentCharge $charge): RedirectResponse
    {
        $appointment->update(['status' => $request->enum('status', AppointmentStatus::class)]);

        match ($appointment->status) {
            AppointmentStatus::Confirmed => $charge->open($appointment),
            AppointmentStatus::Cancelled => $charge->void($appointment),
            default => null,
        };

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
