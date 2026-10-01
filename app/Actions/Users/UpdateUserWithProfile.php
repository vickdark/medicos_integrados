<?php

namespace App\Actions\Users;

use App\Concerns\DoctorValidationRules;
use App\Concerns\PatientValidationRules;
use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateUserWithProfile
{
    use DoctorValidationRules, PatientValidationRules;

    /**
     * @param  array<string, mixed>  $data  Validated form data. A blank password keeps the current one.
     */
    public function handle(User $user, array $data): User
    {
        $role = UserRole::from($data['role']);

        return DB::transaction(function () use ($user, $data, $role): User {
            $user->fill([
                'name' => $role === UserRole::Patient
                    ? trim("{$data['first_name']} {$data['last_name']}")
                    : $data['name'],
                'email' => $data['email'],
                'role' => $role,
            ]);

            if (filled($data['password'] ?? null)) {
                $user->password = $data['password'];
                $user->must_change_password = true;
            }

            $user->save();

            match ($role) {
                UserRole::Doctor => $this->saveDoctor($user, $data),
                UserRole::Patient => $user->patient()->updateOrCreate([], [
                    ...Arr::only($data, self::PATIENT_FIELDS),
                    'email' => $user->email,
                ]),
                default => null,
            };

            AuditLog::record(AuditAction::Updated, $user, "Modificó la cuenta de {$user->name} ({$role->label()})");

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveDoctor(User $user, array $data): void
    {
        $doctor = $user->doctor()->updateOrCreate([], Arr::only($data, self::DOCTOR_FIELDS));

        app(SaveDoctorPhoto::class)->handle($doctor, $data['photo'] ?? null, (bool) ($data['remove_photo'] ?? false));
        app(SaveDoctorSignature::class)->handle($doctor, $data['signature'] ?? null, (bool) ($data['remove_signature'] ?? false));
    }
}
