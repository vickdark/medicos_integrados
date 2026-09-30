<?php

namespace App\Http\Controllers;

use App\Actions\Users\CreateUserWithProfile;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Exports\DoctorsExport;
use App\Exports\TableExporter;
use App\Http\Requests\ExportTableRequest;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\TableQueryRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DoctorController extends Controller
{
    /**
     * Display the medical staff.
     */
    public function index(TableQueryRequest $request): Response
    {
        Gate::authorize('viewAny', Doctor::class);

        $doctors = $this->filteredQuery($request)
            ->with(['user', 'specialty'])
            ->paginate(self::TABLE_PAGE_SIZE)
            ->withQueryString()
            ->through(fn (Doctor $doctor): array => (new DoctorResource($doctor))->resolve($request));

        return Inertia::render('doctors/Index', [
            'doctors' => $doctors,
            'filters' => $request->filters(),
        ]);
    }

    /**
     * Display the profile of a doctor: contact and professional data, office hours
     * and a summary of their activity.
     */
    public function show(Request $request, Doctor $doctor): Response
    {
        Gate::authorize('view', $doctor);

        $doctor->load(['user', 'specialty']);

        return Inertia::render('doctors/Show', [
            'doctor' => (new DoctorResource($doctor))->resolve($request),
            'bio' => $doctor->bio,
            'schedule_summary' => $doctor->weeklyScheduleSummary(),
            'stats' => [
                'appointments_month' => $doctor->appointments()
                    ->where('status', '!=', AppointmentStatus::Cancelled)
                    ->where('scheduled_at', '>=', now()->startOfMonth())
                    ->count(),
                'upcoming' => $doctor->appointments()
                    ->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])
                    ->where('scheduled_at', '>=', now())
                    ->count(),
                'consultations' => $doctor->consultations()->count(),
                'patients' => Patient::query()->treatedBy($doctor)->count(),
            ],
            'can' => [
                'edit' => $request->user()->can('update', $doctor->user),
            ],
        ]);
    }

    /**
     * Export the filtered medical staff to Excel or PDF.
     */
    public function export(ExportTableRequest $request, TableExporter $exporter): SymfonyResponse
    {
        Gate::authorize('viewAny', Doctor::class);

        return $exporter->download(
            new DoctorsExport($this->filteredQuery($request), $request->filters()),
            $request->exportFormat(),
        );
    }

    /**
     * Doctors ordered by name and narrowed by the table search.
     *
     * @return Builder<Doctor>
     */
    private function filteredQuery(TableQueryRequest $request): Builder
    {
        $term = $request->searchTerm();

        return Doctor::query()
            ->when($term, fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
                $query->where('license_number', 'like', "%{$term}%")
                    ->orWhereHas('user', fn (Builder $query) => $query
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"))
                    ->orWhereHas('specialty', fn (Builder $query) => $query->where('name', 'like', "%{$term}%"));
            }))
            ->orderBy(User::query()->select('name')->whereColumn('users.id', 'doctors.user_id'));
    }

    /**
     * Doctors are registered from the users module, which shows the doctor fields.
     */
    public function create(): RedirectResponse
    {
        Gate::authorize('create', Doctor::class);

        return to_route('users.create', ['role' => UserRole::Doctor->value]);
    }

    /**
     * Store the doctor and their user account.
     */
    public function store(StoreDoctorRequest $request, CreateUserWithProfile $createUser): RedirectResponse
    {
        $createUser->handle([...$request->validated(), 'role' => UserRole::Doctor->value]);

        return to_route('doctors.index')->with('success', 'Médico registrado correctamente.');
    }
}
