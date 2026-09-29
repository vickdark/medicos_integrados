<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', Rule::exists(Patient::class, 'id')],
            'appointment_id' => [
                'nullable',
                Rule::exists(Appointment::class, 'id')->where('patient_id', $this->integer('patient_id')),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'status' => ['required', Rule::enum(PaymentStatus::class)->only([PaymentStatus::Paid, PaymentStatus::Pending])],
            'concept' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'required_if:status,paid', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
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
            'appointment_id.exists' => 'La cita seleccionada no pertenece a este paciente.',
            'paid_at.required_if' => 'Indica la fecha en que se realizó el pago.',
        ];
    }
}
