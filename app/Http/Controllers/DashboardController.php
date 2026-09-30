<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\BuildDoctorInsights;
use App\Actions\Dashboard\BuildStaffInsights;
use App\Actions\Turns\DescribePatientTurn;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Middleware\EnsureDeviceId;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\ConsultationResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TourView;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the dashboard adapted to the authenticated user's role.
     */
    public function __invoke(Request $request, BuildStaffInsights $staffInsights, BuildDoctorInsights $doctorInsights, DescribePatientTurn $describePatientTurn): Response
    {
        $user = $request->user();

        $upcomingAppointments = Appointment::query()
            ->visibleTo($user)
            ->with(['patient', 'doctor.user', 'doctor.specialty'])
            ->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $this->statsFor($user),
            'upcomingAppointments' => AppointmentResource::collection($upcomingAppointments),
            'insights' => match ($user->role) {
                UserRole::Admin, UserRole::Receptionist => $staffInsights->handle($user, $request),
                UserRole::Doctor => $doctorInsights->handle($user, $request),
                default => null,
            },
            'profileReminder' => $this->profileReminderFor($user),
            'myTurn' => $user->role === UserRole::Patient && $user->patient
                ? $describePatientTurn->handle($user->patient)
                : null,
            'showTour' => $user->role === UserRole::Patient
                && ! $user->hasSeenTour(TourView::PATIENT_ONBOARDING, EnsureDeviceId::from($request)),
            'recentConsultations' => $user->role === UserRole::Patient && $user->patient
                ? ConsultationResource::collection(
                    $user->patient->consultations()
                        ->with(['doctor.user', 'doctor.specialty', 'primaryDiagnosis'])
                        ->latest('consulted_at')
                        ->limit(3)
                        ->get()
                )
                : [],
        ]);
    }

    /**
     * Reminder asking the patient to fill in or update their data. It is always
     * shown on the dashboard, like the notice on their record.
     *
     * @return array{patient_id: int, incomplete: bool, locked: bool}|null
     */
    private function profileReminderFor(User $user): ?array
    {
        $patient = $user->patient;

        if ($user->role !== UserRole::Patient || ! $patient) {
            return null;
        }

        return [
            'patient_id' => $patient->id,
            'incomplete' => ! $patient->hasCompleteBasicProfile(),
            'locked' => $patient->hasBeenAttended(),
        ];
    }

    /**
     * Build the summary cards for the user's role.
     *
     * @return list<array{label: string, value: int|string}>
     */
    private function statsFor(User $user): array
    {
        return match ($user->role) {
            UserRole::Admin, UserRole::Receptionist => [
                ['label' => 'Pacientes registrados', 'value' => Patient::query()->count()],
                ['label' => 'Citas de hoy', 'value' => Appointment::query()->whereDate('scheduled_at', today())->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])->count()],
                ['label' => 'Solicitudes por confirmar', 'value' => Appointment::query()->where('status', AppointmentStatus::Requested)->count()],
                ['label' => 'Ingresos del mes', 'value' => number_format((float) Payment::query()->where('status', PaymentStatus::Paid)->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'), 2)],
            ],
            UserRole::Doctor => [
                ['label' => 'Citas de hoy', 'value' => Appointment::query()->visibleTo($user)->whereDate('scheduled_at', today())->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])->count()],
                ['label' => 'Solicitudes por confirmar', 'value' => Appointment::query()->visibleTo($user)->where('status', AppointmentStatus::Requested)->count()],
                ['label' => 'Mis pacientes', 'value' => $user->doctor ? Patient::query()->treatedBy($user->doctor)->count() : 0],
                ['label' => 'Consultas realizadas', 'value' => $user->doctor?->consultations()->count() ?? 0],
            ],
            UserRole::Patient => [
                ['label' => 'Próximas citas', 'value' => Appointment::query()->visibleTo($user)->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])->where('scheduled_at', '>=', now())->count()],
                ['label' => 'Consultas en mi historial', 'value' => $user->patient?->consultations()->count() ?? 0],
                ['label' => 'Pagos pendientes', 'value' => number_format((float) ($user->patient?->payments()->where('status', PaymentStatus::Pending)->sum('amount') ?? 0), 2)],
            ],
        };
    }
}
