<?php

namespace App\Http\Controllers;

use App\Actions\Turns\IssueTurn;
use App\Enums\AppointmentStatus;
use App\Enums\TurnStatus;
use App\Http\Requests\StoreTurnRequest;
use App\Http\Requests\UpdateTurnStatusRequest;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Turn;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TurnController extends Controller
{
    /**
     * Show today's queue: the whole clinic for reception, their own for doctors.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Turn::class);

        $user = $request->user();
        $canCreate = $user->can('create', Turn::class);

        $turns = Turn::query()
            ->today()
            ->visibleTo($user)
            ->with(['patient', 'doctor.user', 'appointment'])
            ->orderByRaw('case status when ? then 0 when ? then 1 when ? then 2 else 3 end', [
                TurnStatus::Called->value,
                TurnStatus::Waiting->value,
                TurnStatus::Done->value,
            ])
            ->orderBy('number')
            ->get();

        return Inertia::render('turns/Index', [
            'turns' => $turns->map(fn (Turn $turn): array => [
                ...$this->present($turn, $user),
                'ahead' => $turn->status === TurnStatus::Waiting
                    ? $turns
                        ->where('doctor_id', $turn->doctor_id)
                        ->where('status', TurnStatus::Waiting)
                        ->where('number', '<', $turn->number)
                        ->count()
                    : 0,
            ]),
            'summary' => collect(TurnStatus::cases())
                ->mapWithKeys(fn (TurnStatus $status): array => [
                    $status->value => $turns->where('status', $status)->count(),
                ]),
            'pendingAppointments' => $canCreate ? $this->appointmentsWithoutTurn() : [],
            'patients' => $canCreate
                ? Patient::query()
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get(['id', 'first_name', 'last_name', 'document_number'])
                    ->map(fn (Patient $patient): array => [
                        'value' => $patient->id,
                        'label' => $patient->document_number
                            ? "{$patient->full_name} · {$patient->document_number}"
                            : $patient->full_name,
                    ])
                : [],
            'doctors' => $canCreate
                ? Doctor::query()
                    ->with(['user', 'specialty'])
                    ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
                    ->get()
                    ->sortBy('user.name')
                    ->map(fn (Doctor $doctor): array => [
                        'value' => $doctor->id,
                        'label' => "{$doctor->user->name} · {$doctor->specialty->name}",
                    ])
                    ->values()
                : [],
            'can' => ['create' => $canCreate],
        ]);
    }

    /**
     * Hand out the next turn of the day.
     */
    public function store(StoreTurnRequest $request, IssueTurn $issueTurn): RedirectResponse
    {
        $turn = $issueTurn->handle([
            'patient_id' => $request->patientId(),
            'doctor_id' => $request->doctorId(),
            'appointment_id' => $request->appointment()?->id,
        ], $request->user()->id);

        $turn->load('patient');

        return back()->with('success', "Turno {$turn->code} asignado a {$turn->patient->full_name}.");
    }

    /**
     * Call, finish, requeue or cancel a turn. Calling a turn finishes the one
     * the same doctor was attending, so each doctor has one patient at a time.
     */
    public function updateStatus(UpdateTurnStatusRequest $request, Turn $turn): RedirectResponse
    {
        $status = $request->status();

        DB::transaction(function () use ($turn, $status): void {
            if ($status === TurnStatus::Called) {
                Turn::query()
                    ->today()
                    ->where('doctor_id', $turn->doctor_id)
                    ->where('status', TurnStatus::Called)
                    ->whereKeyNot($turn->id)
                    ->update(['status' => TurnStatus::Done, 'finished_at' => now()]);
            }

            $turn->update([
                'status' => $status,
                'called_at' => match ($status) {
                    TurnStatus::Called => now(),
                    TurnStatus::Waiting => null,
                    default => $turn->called_at,
                },
                'finished_at' => in_array($status, [TurnStatus::Done, TurnStatus::Cancelled], true) ? now() : null,
            ]);
        });

        return back()->with('success', match ($status) {
            TurnStatus::Called => "Turno {$turn->code} llamado.",
            TurnStatus::Done => "Turno {$turn->code} atendido.",
            TurnStatus::Waiting => "Turno {$turn->code} devuelto a la fila.",
            TurnStatus::Cancelled => "Turno {$turn->code} cancelado.",
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Turn $turn, User $user): array
    {
        return [
            'id' => $turn->id,
            'code' => $turn->code,
            'status' => $turn->status->toOption(),
            'patient' => [
                'id' => $turn->patient->id,
                'full_name' => $turn->patient->full_name,
                'document_number' => $turn->patient->document_number,
            ],
            'doctor' => $turn->doctor->user->name,
            'appointment_at' => $turn->appointment?->scheduled_at->toIso8601String(),
            'arrived_at' => $turn->created_at?->toIso8601String(),
            'called_at' => $turn->called_at?->toIso8601String(),
            'can' => [
                'update_status' => $user->can('updateStatus', $turn),
                'attend' => $turn->status === TurnStatus::Called
                    && $user->can('create', [Consultation::class, $turn->patient]),
            ],
            'appointment_id' => $turn->appointment_id,
        ];
    }

    /**
     * Today's active appointments whose patient has not checked in yet.
     *
     * @return list<array{id: int, scheduled_at: string, patient: string, doctor: string}>
     */
    private function appointmentsWithoutTurn(): array
    {
        return Appointment::query()
            ->whereDate('scheduled_at', today())
            ->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])
            ->whereDoesntHave('turns', fn (Builder $query) => $query->where('status', '!=', TurnStatus::Cancelled))
            ->with(['patient', 'doctor.user'])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Appointment $appointment): array => [
                'id' => $appointment->id,
                'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
                'patient' => $appointment->patient->full_name,
                'doctor' => $appointment->doctor->user->name,
            ])
            ->all();
    }
}
