<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('appointment'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedStatuses = $this->user()->isStaff()
            ? [AppointmentStatus::Confirmed, AppointmentStatus::Completed, AppointmentStatus::Cancelled]
            : [AppointmentStatus::Cancelled];

        return [
            'status' => ['required', Rule::enum(AppointmentStatus::class)->only($allowedStatuses)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.enum' => 'No puedes cambiar la cita a ese estado.',
        ];
    }
}
