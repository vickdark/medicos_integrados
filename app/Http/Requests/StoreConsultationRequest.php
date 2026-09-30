<?php

namespace App\Http\Requests;

use App\Enums\DiagnosisType;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Diagnosis;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Consultation::class, $this->route('patient')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'appointment_id' => [
                'nullable',
                Rule::exists(Appointment::class, 'id')->where('patient_id', $this->route('patient')->id),
                Rule::unique(Consultation::class, 'appointment_id'),
            ],
            'reason' => ['required', 'string', 'max:2000'],
            'symptoms' => ['nullable', 'string', 'max:5000'],
            'diagnosis' => ['required', 'string', 'max:5000'],
            'primary_diagnosis_id' => ['required', Rule::exists(Diagnosis::class, 'id')->where('is_active', true)],
            'diagnosis_type' => ['required', Rule::enum(DiagnosisType::class)],
            'related_diagnosis_ids' => ['nullable', 'array', 'max:3'],
            'related_diagnosis_ids.*' => ['distinct', 'different:primary_diagnosis_id', Rule::exists(Diagnosis::class, 'id')->where('is_active', true)],
            'treatment' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'weight_kg' => ['nullable', 'numeric', 'between:0.5,500'],
            'height_cm' => ['nullable', 'numeric', 'between:20,260'],
            'blood_pressure' => ['nullable', 'string', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'temperature_c' => ['nullable', 'numeric', 'between:30,45'],
            'heart_rate' => ['nullable', 'integer', 'between:20,250'],
            'prescriptions' => ['array', 'max:20'],
            'prescriptions.*.medication' => ['required', 'string', 'max:150'],
            'prescriptions.*.dosage' => ['required', 'string', 'max:100'],
            'prescriptions.*.frequency' => ['required', 'string', 'max:100'],
            'prescriptions.*.duration' => ['nullable', 'string', 'max:100'],
            'prescriptions.*.instructions' => ['nullable', 'string', 'max:500'],
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
            'appointment_id.unique' => 'Esta cita ya tiene una consulta registrada.',
            'primary_diagnosis_id.required' => 'Selecciona el diagnóstico principal (CIE-10).',
            'diagnosis_type.required' => 'Indica el tipo de diagnóstico principal.',
            'related_diagnosis_ids.max' => 'Puedes agregar hasta tres diagnósticos relacionados.',
            'related_diagnosis_ids.*.distinct' => 'No repitas diagnósticos relacionados.',
            'related_diagnosis_ids.*.different' => 'Un diagnóstico relacionado no puede ser igual al principal.',
            'blood_pressure.regex' => 'La presión arterial debe tener el formato 120/80.',
        ];
    }
}
