<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DoctorController extends Controller
{
    /**
     * Display the medical staff.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Doctor::class);

        $doctors = Doctor::query()
            ->with(['user', 'specialty'])
            ->join('users', 'users.id', '=', 'doctors.user_id')
            ->orderBy('users.name')
            ->select('doctors.*')
            ->paginate(15)
            ->through(fn (Doctor $doctor): array => (new DoctorResource($doctor))->resolve($request));

        return Inertia::render('doctors/Index', [
            'doctors' => $doctors,
        ]);
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
