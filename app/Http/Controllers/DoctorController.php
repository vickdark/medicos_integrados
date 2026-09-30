<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Exports\DoctorsExport;
use App\Exports\TableExporter;
use App\Http\Requests\ExportTableRequest;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\TableQueryRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Doctor $doctor): array => (new DoctorResource($doctor))->resolve($request));

        return Inertia::render('doctors/Index', [
            'doctors' => $doctors,
            'filters' => $request->filters(),
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
     * Show the form to register a doctor.
     */
    public function create(): Response
    {
        Gate::authorize('create', Doctor::class);

        return Inertia::render('doctors/Create', [
            'specialties' => Specialty::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store the doctor and their user account.
     */
    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::create([
                ...$request->safe()->only(['name', 'email', 'password']),
                'role' => UserRole::Doctor,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $user->doctor()->create($request->safe()->only([
                'specialty_id',
                'license_number',
                'phone',
                'consultation_fee',
                'bio',
            ]));
        });

        return to_route('doctors.index')->with('success', 'Médico registrado correctamente.');
    }
}
