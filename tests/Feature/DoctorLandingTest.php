<?php

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Account and doctor data accepted by the users form.
 *
 * @return array<string, mixed>
 */
function doctorFormData(array $overrides = []): array
{
    return [
        'role' => 'doctor',
        'name' => 'Dra. Ana Vera',
        'email' => 'ana.vera@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'specialty_id' => Specialty::factory()->create()->id,
        'license_number' => 'CMP-777',
        'consultation_fee' => '50',
        'slot_minutes' => 30,
        ...$overrides,
    ];
}

it('does not show the doctors on the landing page until the administrator enables it', function () {
    Doctor::factory()->count(2)->create();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('doctors', null));

    expect(AppSetting::showDoctorsOnLanding())->toBeFalse();
});

it('shows the active doctors with their basic public profile once enabled', function () {
    $visible = Doctor::factory()->create(['bio' => 'Cardióloga con vocación docente.']);
    $hidden = Doctor::factory()->create();
    $hidden->user->forceFill(['is_active' => false])->save();
    AppSetting::setShowDoctorsOnLanding(true);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('doctors', 1)
            ->where('doctors.0.id', $visible->id)
            ->where('doctors.0.name', $visible->user->name)
            ->where('doctors.0.specialty', $visible->specialty->name)
            ->where('doctors.0.bio', 'Cardióloga con vocación docente.')
            ->missing('doctors.0.license_number')
            ->missing('doctors.0.consultation_fee')
            ->missing('doctors.0.email')
        );
});

it('lets the administrator enable and disable the public list of doctors', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('landing-settings.update'), ['show_doctors' => '1'])
        ->assertSessionHasNoErrors();

    expect(AppSetting::showDoctorsOnLanding())->toBeTrue()
        ->and(AuditLog::query()->where('description', 'like', 'Activó la vista pública%')->exists())->toBeTrue();

    $this->actingAs($admin)->put(route('landing-settings.update'), ['show_doctors' => '0']);

    expect(AppSetting::showDoctorsOnLanding())->toBeFalse();

    $this->actingAs($admin)
        ->get(route('branding.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('showDoctors', false));
});

it('does not let anyone but the administrator change the landing settings', function () {
    foreach (['receptionist', 'doctor', 'patient'] as $state) {
        $this->actingAs(User::factory()->{$state}()->create())
            ->put(route('landing-settings.update'), ['show_doctors' => '1'])
            ->assertForbidden();
    }

    expect(AppSetting::showDoctorsOnLanding())->toBeFalse();
});

it('lets the administrator upload the photo when creating a doctor', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), doctorFormData(['photo' => UploadedFile::fake()->image('ana.jpg', 400, 500)]))
        ->assertSessionHasNoErrors();

    $doctor = Doctor::query()->sole();

    expect($doctor->photo_path)->not->toBeNull()
        ->and($doctor->photoUrl())->toContain('/doctors/'.$doctor->id.'/photo');

    Storage::disk('local')->assertExists($doctor->photo_path);
});

it('replaces and removes the photo of a doctor', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();
    $payload = fn (array $extra = []) => [
        'role' => 'doctor',
        'name' => $doctor->user->name,
        'email' => $doctor->user->email,
        'specialty_id' => $doctor->specialty_id,
        'license_number' => $doctor->license_number,
        'consultation_fee' => '50',
        'slot_minutes' => 30,
        ...$extra,
    ];

    $this->actingAs($admin)->put(route('users.update', $doctor->user), $payload(['photo' => UploadedFile::fake()->image('uno.png')]));
    $first = $doctor->fresh()->photo_path;

    $this->actingAs($admin)->put(route('users.update', $doctor->user), $payload(['photo' => UploadedFile::fake()->image('dos.png')]));
    $second = $doctor->fresh()->photo_path;

    expect($second)->not->toBe($first);
    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists($second);

    $this->actingAs($admin)->put(route('users.update', $doctor->user), $payload(['remove_photo' => '1']));

    expect($doctor->fresh()->photo_path)->toBeNull();
    Storage::disk('local')->assertMissing($second);
});

it('rejects photos that are not images or are too large', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), doctorFormData(['photo' => UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf')]))
        ->assertSessionHasErrors('photo');

    $this->actingAs($admin)
        ->post(route('users.store'), doctorFormData(['email' => 'otra@example.com', 'photo' => UploadedFile::fake()->image('grande.jpg')->size(3000)]))
        ->assertSessionHasErrors('photo');

    expect(Doctor::query()->count())->toBe(0);
});

it('serves the photo publicly only while the doctors are shown on the landing page', function () {
    $doctor = Doctor::factory()->create(['photo_path' => UploadedFile::fake()->image('foto.jpg')->store('doctor-photos', 'local')]);

    $this->get(route('doctors.photo', $doctor))->assertNotFound();

    AppSetting::setShowDoctorsOnLanding(true);

    $this->get(route('doctors.photo', $doctor))->assertOk();

    $doctor->user->forceFill(['is_active' => false])->save();

    $this->get(route('doctors.photo', $doctor))->assertNotFound();
});

it('lets signed in users see the photo even when the landing list is off', function () {
    $doctor = Doctor::factory()->create(['photo_path' => UploadedFile::fake()->image('foto.jpg')->store('doctor-photos', 'local')]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('doctors.photo', $doctor))
        ->assertOk();
});

it('returns not found for doctors without a photo', function () {
    $doctor = Doctor::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('doctors.photo', $doctor))
        ->assertNotFound();
});
