<?php

namespace App\Http\Requests;

use App\Concerns\DoctorValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Concerns\PatientValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use DoctorValidationRules, PasswordValidationRules, PatientValidationRules, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request. The profile rules
     * depend on the role chosen in the form.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $account = [
            'role' => ['required', Rule::enum(UserRole::class)],
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
        ];

        return match ($this->enum('role', UserRole::class)) {
            UserRole::Patient => [...$account, ...$this->patientProfileRules()],
            UserRole::Doctor => [...$account, 'name' => $this->nameRules(), ...$this->doctorRules()],
            default => [...$account, 'name' => $this->nameRules()],
        };
    }

    /**
     * The patient fields. When the clinic already registered a patient with this
     * email, their document number is not reported as a duplicate of itself.
     *
     * @return array<string, array<int, mixed>>
     */
    private function patientProfileRules(): array
    {
        $existingPatientId = Patient::query()
            ->whereNull('user_id')
            ->where('email', $this->input('email'))
            ->value('id');

        return Arr::except($this->patientRules($existingPatientId), 'email');
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
            'role.required' => 'Selecciona el rol del usuario.',
            'role.enum' => 'El rol seleccionado no es válido.',
        ];
    }
}
