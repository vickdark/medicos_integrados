<?php

namespace App\Actions\Users;

use App\Concerns\DoctorValidationRules;
use App\Concerns\PatientValidationRules;
use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Creates a user account together with the profile that matches its role, so
 * nothing has to be completed later from another module.
 */
class CreateUserWithProfile
{
    use DoctorValidationRules, PatientValidationRules;

    /**
     * @param  array<string, mixed>  $data  Validated form data: role, account fields and role fields.
     */
    public function handle(array $data): User
    {
        $role = UserRole::from($data['role']);

        return DB::transaction(function () use ($data, $role): User {
            $user = new User([
                'name' => $this->accountName($role, $data),
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $role,
            ]);
            $user->email_verified_at = now();
            $user->must_change_password = true;
            $user->save();

            match ($role) {
                UserRole::Doctor => $this->saveDoctor($user, $data),
                UserRole::Patient => $this->attachPatientRecord($user, $data),
                default => null,
            };

            AuditLog::record(AuditAction::Created, $user, "Creó la cuenta de {$user->name} ({$role->label()})");

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveDoctor(User $user, array $data): void
    {
        $doctor = $user->doctor()->create(Arr::only($data, self::DOCTOR_FIELDS));

        app(SaveDoctorPhoto::class)->handle($doctor, $data['photo'] ?? null, (bool) ($data['remove_photo'] ?? false));
        app(SaveDoctorSignature::class)->handle($doctor, $data['signature'] ?? null, (bool) ($data['remove_signature'] ?? false));
    }

    /**
     * Patients are identified by first and last name; everyone else by a single name.
     *
     * @param  array<string, mixed>  $data
     */
    private function accountName(UserRole $role, array $data): string
    {
        return $role === UserRole::Patient
            ? trim("{$data['first_name']} {$data['last_name']}")
            : $data['name'];
    }

    /**
     * Link the account to the record the clinic already registered with the same
     * email, or create a new one.
     *
     * @param  array<string, mixed>  $data
     */
    private function attachPatientRecord(User $user, array $data): void
    {
        $attributes = [...Arr::only($data, self::PATIENT_FIELDS), 'email' => $user->email];

        $existingPatient = Patient::query()
            ->whereNull('user_id')
            ->where('email', $user->email)
            ->first();

        if ($existingPatient) {
            $existingPatient->fill($attributes)->user()->associate($user)->save();

            return;
        }

        $user->patient()->create($attributes);
    }
}
