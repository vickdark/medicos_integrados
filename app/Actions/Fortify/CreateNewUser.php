<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'role' => UserRole::Patient,
            ]);

            $this->attachPatientRecord($user);

            return $user;
        });
    }

    /**
     * Link the user to the patient record registered with the same email, or create a new one.
     */
    private function attachPatientRecord(User $user): void
    {
        $existingPatient = Patient::query()
            ->whereNull('user_id')
            ->where('email', $user->email)
            ->first();

        if ($existingPatient) {
            $existingPatient->user()->associate($user)->save();

            return;
        }

        [$firstName, $lastName] = array_pad(explode(' ', trim($user->name), 2), 2, '');

        $user->patient()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email,
        ]);
    }
}
