<?php

namespace App\Http\Requests;

use App\Concerns\DoctorValidationRules;
use App\Concerns\PatientValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    use DoctorValidationRules, PatientValidationRules, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request. The profile rules
     * depend on the role of the account being edited.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $account */
        $account = $this->route('user');

        $base = [
            'role' => ['required', Rule::enum(UserRole::class)->only($account->assignableRoles($this->user()))],
            'email' => $this->emailRules($account->id),
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
        ];

        return match ($account->role) {
            UserRole::Patient => [...$base, ...Arr::except($this->patientRules($account->patient?->id), 'email')],
            UserRole::Doctor => [...$base, 'name' => $this->nameRules(), ...$this->doctorRules($account->doctor?->id)],
            default => [...$base, 'name' => $this->nameRules()],
        };
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.image' => 'La foto debe ser una imagen.',
            'photo.mimes' => 'La foto debe ser JPG, PNG o WebP.',
            'photo.max' => 'La foto no puede superar los 2 MB.',
            'photo.uploaded' => 'No se pudo subir la foto; revisa que pese menos de 2 MB.',
            'role.enum' => 'No se puede cambiar el rol de esta cuenta.',
        ];
    }
}
