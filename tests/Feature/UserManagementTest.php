<?php

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use App\Notifications\AccountCredentials;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

/**
 * Account fields shared by every role.
 *
 * @return array<string, string>
 */
function accountData(string $email = 'nuevo@example.com'): array
{
    return [
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ];
}

it('lists, searches and filters the users for the admin', function () {
    User::factory()->create(['name' => 'Beatriz Lozano', 'email' => 'beatriz@example.com']);
    User::factory()->receptionist()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/Index')
            ->has('users.data', 3)
            ->has('roles', 4)
        );

    $this->actingAs($admin)
        ->get(route('users.index', ['search' => 'Lozano']))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 1));

    $this->actingAs($admin)
        ->get(route('users.index', ['role' => 'receptionist']))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 1));
});

it('forbids non admins from managing users', function (string $factoryState) {
    $user = User::factory()->{$factoryState}()->create();
    $other = User::factory()->create();

    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('users.create'))->assertForbidden();
    $this->actingAs($user)->post(route('users.store'), ['role' => 'admin', 'name' => 'X', ...accountData()])->assertForbidden();
    $this->actingAs($user)->get(route('users.edit', $other))->assertForbidden();
    $this->actingAs($user)->put(route('users.update', $other), ['role' => 'patient'])->assertForbidden();
    $this->actingAs($user)->get(route('users.export', ['format' => 'xlsx']))->assertForbidden();
})->with(['receptionist', 'doctor', 'patient']);

it('creates administration accounts without any profile', function (UserRole $role) {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), ['role' => $role->value, 'name' => 'Laura Gómez', ...accountData()])
        ->assertRedirect(route('users.index'))
        ->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'nuevo@example.com')->sole();

    expect($user->role)->toBe($role)
        ->and($user->name)->toBe('Laura Gómez')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->doctor)->toBeNull()
        ->and($user->patient)->toBeNull();
})->with([UserRole::Admin, UserRole::Receptionist]);

it('creates a doctor account and profile in one step', function () {
    $specialty = Specialty::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), [
            'role' => 'doctor',
            'name' => 'Dr. Mario Ruiz',
            ...accountData('mario@example.com'),
            'specialty_id' => $specialty->id,
            'license_number' => 'CMP-99001',
            'phone' => '999 111 222',
            'consultation_fee' => '75',
            'bio' => 'Cardiólogo con 10 años de experiencia',
        ])
        ->assertSessionHasNoErrors();

    $doctor = Doctor::query()->with('user')->sole();

    expect($doctor->user->role)->toBe(UserRole::Doctor)
        ->and($doctor->user->name)->toBe('Dr. Mario Ruiz')
        ->and($doctor->specialty_id)->toBe($specialty->id)
        ->and($doctor->license_number)->toBe('CMP-99001')
        ->and($doctor->consultation_fee)->toBe('75.00');
});

it('creates a patient account and record in one step', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), [
            'role' => 'patient',
            ...accountData('lucia@example.com'),
            'first_name' => 'Lucía',
            'last_name' => 'Fernández Soto',
            'document_number' => '44556677',
            'document_type' => 'CC',
            'birth_date' => '1992-03-14',
            'gender' => 'female',
            'blood_type' => 'O+',
            'allergies' => 'Penicilina',
        ])
        ->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'lucia@example.com')->sole();

    expect($user->role)->toBe(UserRole::Patient)
        ->and($user->name)->toBe('Lucía Fernández Soto')
        ->and($user->patient->document_number)->toBe('44556677')
        ->and($user->patient->email)->toBe('lucia@example.com')
        ->and($user->patient->allergies)->toBe('Penicilina')
        ->and($user->patient->getRawOriginal('allergies'))->not->toBe('Penicilina');
});

it('links a new patient account to the record the clinic already registered', function () {
    $existing = Patient::factory()->create(['email' => 'pedro@example.com', 'document_number' => '11223344']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), [
            'role' => 'patient',
            ...accountData('pedro@example.com'),
            'first_name' => 'Pedro',
            'last_name' => 'Ramos',
            'document_number' => '11223344',
            'document_type' => 'CC',
        ])
        ->assertSessionHasNoErrors();

    expect(Patient::query()->count())->toBe(1)
        ->and($existing->refresh()->user->email)->toBe('pedro@example.com')
        ->and($existing->last_name)->toBe('Ramos');
});

it('validates the fields of the chosen role', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), ['role' => 'doctor', 'name' => 'Dr. X', ...accountData()])
        ->assertSessionHasErrors(['specialty_id', 'license_number', 'consultation_fee']);

    $this->actingAs($admin)
        ->post(route('users.store'), ['role' => 'patient', ...accountData()])
        ->assertSessionHasErrors(['first_name', 'last_name'])
        ->assertSessionDoesntHaveErrors('name');

    $this->actingAs($admin)
        ->post(route('users.store'), ['role' => 'receptionist', ...accountData()])
        ->assertSessionHasErrors('name');

    $this->actingAs($admin)
        ->post(route('users.store'), ['name' => 'Sin rol', ...accountData()])
        ->assertSessionHasErrors('role');

    $this->actingAs($admin)
        ->post(route('users.store'), ['role' => 'superuser', 'name' => 'X', ...accountData()])
        ->assertSessionHasErrors('role');

    expect(User::query()->count())->toBe(1);
});

it('rejects duplicated emails and ignores profile fields of other roles', function () {
    User::factory()->create(['email' => 'repetido@example.com']);
    $specialty = Specialty::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), ['role' => 'admin', 'name' => 'A', ...accountData('repetido@example.com')])
        ->assertSessionHasErrors('email');

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), [
            'role' => 'receptionist',
            'name' => 'Recepción',
            ...accountData('recepcion2@example.com'),
            'specialty_id' => $specialty->id,
            'license_number' => 'X-1',
            'consultation_fee' => '10',
        ])
        ->assertSessionHasNoErrors();

    expect(Doctor::query()->count())->toBe(0);
});

it('lets a newly created doctor sign in and reach their own module', function () {
    $specialty = Specialty::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), [
            'role' => 'doctor',
            'name' => 'Dra. Ana Vera',
            ...accountData('ana@example.com'),
            'specialty_id' => $specialty->id,
            'license_number' => 'CMP-1',
            'consultation_fee' => '50',
        ]);

    auth()->logout();

    $this->post(route('login.store'), ['email' => 'ana@example.com', 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->get(route('dashboard'))->assertRedirect(route('security.edit'));

    $this->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'MiClave-segura-456',
        'password_confirmation' => 'MiClave-segura-456',
    ])->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('auth.role.value', 'doctor')
        ->where('auth.doctorId', Doctor::query()->sole()->id)
    );
});

it('records the creation and the changes of accounts in the audit trail', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), ['role' => 'receptionist', 'name' => 'Rita Paz', ...accountData()]);

    $user = User::query()->where('email', 'nuevo@example.com')->sole();

    $this->actingAs($admin)
        ->put(route('users.update', $user), ['role' => 'receptionist', 'name' => 'Rita Paz Soto', 'email' => 'nuevo@example.com']);

    expect(AuditLog::query()->where('action', AuditAction::Created)->where('description', 'like', '%Rita Paz%Recepción%')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::Updated)->where('user_id', $admin->id)->exists())->toBeTrue();
});

it('shows the role fields already filled when editing', function () {
    $doctor = Doctor::factory()->create(['license_number' => 'CMP-555']);
    $patient = Patient::factory()->withAccount()->create(['allergies' => 'Polen']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('users.edit', $doctor->user))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/Form')
            ->where('user.role', 'doctor')
            ->where('doctor.license_number', 'CMP-555')
            ->has('roleOptions', 1)
        );

    $this->actingAs($admin)
        ->get(route('users.edit', $patient->user))
        ->assertInertia(fn (Assert $page) => $page
            ->where('user.role', 'patient')
            ->where('patient.allergies', 'Polen')
            ->has('roleOptions', 1)
        );

    $this->actingAs($admin)
        ->get(route('users.edit', User::factory()->receptionist()->create()))
        ->assertInertia(fn (Assert $page) => $page->has('roleOptions', 2));
});

it('updates the account and the doctor profile together', function () {
    $doctor = Doctor::factory()->create();
    $newSpecialty = Specialty::factory()->create();
    $originalPassword = $doctor->user->password;

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('users.update', $doctor->user), [
            'role' => 'doctor',
            'name' => 'Dr. Nombre Nuevo',
            'email' => 'nuevo-correo@example.com',
            'specialty_id' => $newSpecialty->id,
            'license_number' => $doctor->license_number,
            'consultation_fee' => '90',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHasNoErrors();

    $doctor->refresh();

    expect($doctor->user->name)->toBe('Dr. Nombre Nuevo')
        ->and($doctor->user->email)->toBe('nuevo-correo@example.com')
        ->and($doctor->specialty_id)->toBe($newSpecialty->id)
        ->and($doctor->consultation_fee)->toBe('90.00')
        ->and($doctor->user->password)->toBe($originalPassword);
});

it('updates the patient record and keeps the account in sync', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('users.update', $patient->user), [
            'role' => 'patient',
            'email' => 'paciente-nuevo@example.com',
            'first_name' => 'Carla',
            'last_name' => 'Mena',
            'document_number' => $patient->document_number,
            'document_type' => 'CC',
            'phone' => '987 654 321',
        ])
        ->assertSessionHasNoErrors();

    $patient->refresh();

    expect($patient->user->name)->toBe('Carla Mena')
        ->and($patient->user->email)->toBe('paciente-nuevo@example.com')
        ->and($patient->email)->toBe('paciente-nuevo@example.com')
        ->and($patient->phone)->toBe('987 654 321');
});

it('changes the password only when a new one is provided', function () {
    $user = User::factory()->receptionist()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'role' => 'receptionist',
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('nueva-clave-123', $user->refresh()->password))->toBeTrue();

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'role' => 'receptionist',
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'otra-clave-456',
            'password_confirmation' => 'no-coincide',
        ])
        ->assertSessionHasErrors('password');
});

it('only lets administration roles be swapped and protects the own account', function () {
    $admin = User::factory()->admin()->create();
    $receptionist = User::factory()->receptionist()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $receptionist), ['role' => 'admin', 'name' => $receptionist->name, 'email' => $receptionist->email])
        ->assertSessionHasNoErrors();

    expect($receptionist->refresh()->role)->toBe(UserRole::Admin);

    $this->actingAs($admin)
        ->put(route('users.update', $doctor->user), [
            'role' => 'admin',
            'name' => $doctor->user->name,
            'email' => $doctor->user->email,
            'specialty_id' => $doctor->specialty_id,
            'license_number' => $doctor->license_number,
            'consultation_fee' => '10',
        ])
        ->assertSessionHasErrors('role');

    expect($doctor->user->refresh()->role)->toBe(UserRole::Doctor);

    $this->actingAs($admin)
        ->put(route('users.update', $admin), ['role' => 'receptionist', 'name' => $admin->name, 'email' => $admin->email])
        ->assertSessionHasErrors('role');

    expect($admin->refresh()->role)->toBe(UserRole::Admin);
});

it('exports the filtered users', function () {
    User::factory()->count(2)->receptionist()->create();
    Doctor::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('users.export', ['format' => 'xlsx', 'role' => 'receptionist']))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)
        ->get(route('users.export', ['format' => 'pdf']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('sends the doctor registration shortcut to the unified user form', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('doctors.create'))
        ->assertRedirect(route('users.create', ['role' => 'doctor']));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('users.create', ['role' => 'doctor']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/Form')
            ->where('initialRole', 'doctor')
            ->has('specialties')
        );
});

it('emails the access details right away when the admin asks for it', function () {
    Notification::fake();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), [
            'role' => 'receptionist',
            'name' => 'Rita Paz',
            ...accountData('rita@example.com'),
            'send_credentials' => 'on',
        ])
        ->assertSessionHas('success', fn (string $message) => str_contains($message, 'enviaron por correo'));

    $user = User::query()->where('email', 'rita@example.com')->sole();

    Notification::assertSentTo($user, AccountCredentials::class, fn (AccountCredentials $notification) => $notification->temporaryPassword === 'password'
        && $notification->isNewAccount
        && ! $notification instanceof ShouldQueue);
});

it('does not email anything when the option is left unchecked', function () {
    Notification::fake();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), ['role' => 'receptionist', 'name' => 'Rita Paz', ...accountData()])
        ->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

it('sends the credentials for doctors and patients too', function () {
    Notification::fake();
    $specialty = Specialty::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('users.store'), [
        'role' => 'doctor',
        'name' => 'Dr. Mario Ruiz',
        ...accountData('mario@example.com'),
        'specialty_id' => $specialty->id,
        'license_number' => 'CMP-77',
        'consultation_fee' => '60',
        'send_credentials' => 'on',
    ]);

    $this->actingAs($admin)->post(route('users.store'), [
        'role' => 'patient',
        ...accountData('lucia@example.com'),
        'first_name' => 'Lucía',
        'last_name' => 'Soto',
        'send_credentials' => 'on',
    ]);

    Notification::assertSentTo(User::query()->where('email', 'mario@example.com')->sole(), AccountCredentials::class);
    Notification::assertSentTo(User::query()->where('email', 'lucia@example.com')->sole(), AccountCredentials::class);
});

it('writes the access details in Spanish with the login link', function () {
    $user = User::factory()->doctor()->create(['name' => 'Dr. Mario Ruiz', 'email' => 'mario@example.com']);

    $mail = (new AccountCredentials('Temporal-123'))->toMail($user);
    $body = implode("\n", $mail->introLines);

    expect($mail->subject)->toContain('Tus datos de acceso')
        ->and($body)->toContain('Médico')
        ->and($body)->toContain('mario@example.com')
        ->and($body)->toContain('Temporal-123')
        ->and($mail->actionUrl)->toBe(route('login'));

    $update = (new AccountCredentials('Nueva-456', isNewAccount: false))->toMail($user);

    expect($update->subject)->toContain('contraseña')->toContain('actualizada');
});

it('emails the new password only when an admin changes it', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $user = User::factory()->receptionist()->create();
    $payload = ['role' => 'receptionist', 'name' => $user->name, 'email' => $user->email, 'send_credentials' => 'on'];

    $this->actingAs($admin)->put(route('users.update', $user), $payload)->assertSessionHasNoErrors();

    Notification::assertNothingSent();

    $this->actingAs($admin)->put(route('users.update', $user), [
        ...$payload,
        'password' => 'Nueva-clave-789',
        'password_confirmation' => 'Nueva-clave-789',
    ])->assertSessionHasNoErrors();

    Notification::assertSentTo($user, AccountCredentials::class, fn (AccountCredentials $notification) => $notification->temporaryPassword === 'Nueva-clave-789'
        && ! $notification->isNewAccount);
});

it('keeps the account and warns the admin when the email cannot be sent', function () {
    $this->mock(NotificationDispatcher::class, function (MockInterface $mock): void {
        $mock->shouldReceive('sendNow')->andThrow(new RuntimeException('SMTP caído'));
    });

    $this->withoutExceptionHandling()
        ->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), [
            'role' => 'receptionist',
            'name' => 'Rita Paz',
            ...accountData('rita@example.com'),
            'send_credentials' => 'on',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('error', fn (string $message) => str_contains($message, 'no se pudo enviar el correo'));

    expect(User::query()->where('email', 'rita@example.com')->exists())->toBeTrue();
});

it('marks accounts created by the admin as having a temporary password', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('users.store'), [
        'role' => 'receptionist',
        'name' => 'Rosa Pérez',
        ...accountData('rosa@example.com'),
    ])->assertRedirect(route('users.index'));

    expect(User::query()->where('email', 'rosa@example.com')->sole()->must_change_password)->toBeTrue();
});

it('marks the account temporary again only when the admin sets a new password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->receptionist()->create();
    $payload = ['role' => 'receptionist', 'name' => $user->name, 'email' => $user->email];

    $this->actingAs($admin)->put(route('users.update', $user), $payload)->assertRedirect();
    expect($user->fresh()->must_change_password)->toBeFalse();

    $this->actingAs($admin)->put(route('users.update', $user), [...$payload, 'password' => 'Nueva-clave-789', 'password_confirmation' => 'Nueva-clave-789'])->assertRedirect();
    expect($user->fresh()->must_change_password)->toBeTrue();
});

it('keeps users with a temporary password on the security page', function () {
    $user = User::factory()->receptionist()->create(['must_change_password' => true]);

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('security.edit'));
    $this->actingAs($user)->get(route('patients.index'))->assertRedirect(route('security.edit'));
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->get(route('security.edit'))->assertOk();
});

it('releases the user once they choose their own password', function () {
    $user = User::factory()->receptionist()->create(['must_change_password' => true, 'password' => 'temporal-123']);

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'temporal-123',
        'password' => 'MiClave-segura-456',
        'password_confirmation' => 'MiClave-segura-456',
    ])->assertRedirect(route('dashboard'));

    expect($user->fresh()->must_change_password)->toBeFalse();
    $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
});

it('shows the access status and filters users by it', function () {
    User::factory()->receptionist()->create(['name' => 'Ines Activa']);
    User::factory()->receptionist()->create(['name' => 'Ivo Inactivo', 'is_active' => false]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('users.index', ['status' => 'inactive']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Ivo Inactivo')
            ->where('users.data.0.is_active', false)
        );
});

it('lets the admin deactivate and reactivate an account', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->receptionist()->create();

    $this->actingAs($admin)->patch(route('users.status', $user))->assertRedirect();
    expect($user->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->patch(route('users.status', $user))->assertRedirect();
    expect($user->fresh()->is_active)->toBeTrue();
    expect(AuditLog::query()->where('description', 'like', 'Inactivó%')->exists())->toBeTrue();
});

it('does not let admins deactivate themselves or non-admins manage access', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->receptionist()->create();

    $this->actingAs($admin)->patch(route('users.status', $admin))->assertForbidden();
    $this->actingAs($other)->patch(route('users.status', $admin))->assertForbidden();
    expect($admin->fresh()->is_active)->toBeTrue();
});

it('blocks sign in for inactive accounts', function () {
    User::factory()->receptionist()->create(['email' => 'off@example.com', 'is_active' => false]);

    $this->post(route('login.store'), ['email' => 'off@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('signs out an account that is deactivated while logged in', function () {
    $user = User::factory()->receptionist()->create(['is_active' => false]);

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
});
