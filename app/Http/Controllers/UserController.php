<?php

namespace App\Http\Controllers;

use App\Actions\Users\CreateUserWithProfile;
use App\Actions\Users\UpdateUserWithProfile;
use App\Enums\AuditAction;
use App\Enums\Gender;
use App\Enums\UserRole;
use App\Exports\TableExporter;
use App\Exports\UsersExport;
use App\Http\Requests\ExportTableRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\TableQueryRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\PatientResource;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\Specialty;
use App\Models\User;
use App\Notifications\AccountCredentials;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class UserController extends Controller
{
    /**
     * Display the user accounts.
     */
    public function index(TableQueryRequest $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $users = $this->filteredQuery($request)
            ->with(['doctor.specialty', 'patient'])
            ->paginate(self::TABLE_PAGE_SIZE)
            ->withQueryString()
            ->through(fn (User $user): array => (new UserResource($user))->resolve($request));

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => $request->filters(),
            'roles' => UserRole::options(),
        ]);
    }

    /**
     * Export the filtered accounts to Excel or PDF.
     */
    public function export(ExportTableRequest $request, TableExporter $exporter): SymfonyResponse
    {
        Gate::authorize('viewAny', User::class);

        return $exporter->download(
            new UsersExport($this->filteredQuery($request), $request->filters()),
            $request->exportFormat(),
        );
    }

    /**
     * Show the form to create an account. The role decides which fields appear.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('users/Form', [
            'user' => null,
            'doctor' => null,
            'patient' => null,
            'initialRole' => UserRole::tryFrom($request->string('role')->toString())?->value ?? '',
            'roleOptions' => UserRole::options(),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store the account and the profile of its role.
     */
    public function store(StoreUserRequest $request, CreateUserWithProfile $createUser): RedirectResponse
    {
        $user = $createUser->handle($request->validated());

        $emailWarning = $request->boolean('send_credentials')
            ? $this->sendCredentials($user, $request->validated('password'), isNewAccount: true)
            : null;

        return to_route('users.index')
            ->with('success', "Cuenta de {$user->name} creada como {$user->role->label()}."
                .($request->boolean('send_credentials') && ! $emailWarning ? ' Los datos de acceso se enviaron por correo.' : ''))
            ->with('error', $emailWarning);
    }

    /**
     * Show the form to edit the account and the profile of its role.
     */
    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);

        $user->load(['doctor', 'patient']);

        return Inertia::render('users/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
            'doctor' => $user->doctor?->only(['specialty_id', 'license_number', 'phone', 'consultation_fee', 'slot_minutes', 'bio']),
            'patient' => $user->patient ? (new PatientResource($user->patient))->resolve($request) : null,
            'initialRole' => $user->role->value,
            'roleOptions' => array_map(fn (UserRole $role): array => $role->toOption(), $user->assignableRoles($request->user())),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the account and the profile of its role.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUserWithProfile $updateUser): RedirectResponse
    {
        $updateUser->handle($user, $request->validated());

        $newPassword = $request->validated('password');
        $shouldSend = $request->boolean('send_credentials') && filled($newPassword);

        $emailWarning = $shouldSend
            ? $this->sendCredentials($user, $newPassword, isNewAccount: false)
            : null;

        return to_route('users.index')
            ->with('success', 'Cuenta actualizada correctamente.'.($shouldSend && ! $emailWarning ? ' La nueva contraseña se envió por correo.' : ''))
            ->with('error', $emailWarning);
    }

    /**
     * Activate or deactivate the account's access. Deactivating also drops its
     * remember token, and the account is signed out on its next request.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        Gate::authorize('toggleStatus', $user);

        $isActive = ! $user->is_active;
        $user->forceFill(['is_active' => $isActive, 'remember_token' => null])->save();

        AuditLog::record(AuditAction::Updated, $user, ($isActive ? 'Activó' : 'Inactivó')." el acceso de {$user->name}");

        return back()->with('success', $isActive
            ? "Se activó el acceso de {$user->name}."
            : "Se inactivó el acceso de {$user->name}.");
    }

    /**
     * Email the access details right away (never queued, so the password is not
     * stored in the jobs table). A mail failure must not undo the account, so it
     * is reported and returned as a warning for the admin.
     */
    private function sendCredentials(User $user, string $password, bool $isNewAccount): ?string
    {
        try {
            $user->notifyNow(new AccountCredentials($password, $isNewAccount));
        } catch (Throwable $exception) {
            report($exception);

            return "La cuenta de {$user->name} se guardó, pero no se pudo enviar el correo. Comparte los datos de acceso de otra forma.";
        }

        return null;
    }

    /**
     * Accounts narrowed by the table search and role filter, ordered by name.
     *
     * @return Builder<User>
     */
    private function filteredQuery(TableQueryRequest $request): Builder
    {
        $term = $request->searchTerm();
        $role = UserRole::tryFrom($request->string('role')->toString());
        $status = $request->string('status')->toString();

        return User::query()
            ->when($term, fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            }))
            ->when($role, fn (Builder $query) => $query->where('role', $role))
            ->when(in_array($status, ['active', 'inactive'], true), fn (Builder $query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name');
    }

    /**
     * Select options needed by the role-specific sections of the form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'specialties' => Specialty::query()->orderBy('name')->get(['id', 'name']),
            'genders' => Gender::options(),
            'bloodTypes' => ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'],
        ];
    }
}
