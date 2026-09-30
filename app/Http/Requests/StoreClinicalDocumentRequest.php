<?php

namespace App\Http\Requests;

use App\Enums\CarePriority;
use App\Enums\ClinicalDocumentType;
use App\Enums\SickLeaveOrigin;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClinicalDocumentRequest extends FormRequest
{
    /**
     * Longest sick leave a single certificate may cover; longer ones are issued
     * as extensions.
     */
    public const MAX_SICK_LEAVE_DAYS = 30;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('issueDocuments', $this->route('consultation'));
    }

    /**
     * Get the validation rules that apply to the request. Each document type has
     * its own fields.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ClinicalDocumentType::class)],
            ...$this->fieldRules(),
        ];
    }

    /**
     * The content of the document, without the fields of other types.
     *
     * @return array<string, mixed>
     */
    public function documentData(): array
    {
        $data = Arr::only($this->validated(), array_keys($this->fieldRules()));

        return match ($this->documentType()) {
            ClinicalDocumentType::SickLeave => [
                ...$data,
                'days' => (int) $data['days'],
                'is_extension' => $this->boolean('is_extension'),
            ],
            ClinicalDocumentType::ExamOrder => [
                ...$data,
                'exams' => array_values(array_filter(array_map(fn (?string $exam): string => trim((string) $exam), $data['exams']))),
            ],
            default => $data,
        };
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->documentType() !== ClinicalDocumentType::ExamOrder || $validator->errors()->has('exams')) {
                    return;
                }

                if (array_filter((array) $this->input('exams'), fn ($exam): bool => filled($exam)) === []) {
                    $validator->errors()->add('exams', 'Agrega al menos un examen.');
                }
            },
        ];
    }

    public function documentType(): ?ClinicalDocumentType
    {
        return ClinicalDocumentType::tryFrom($this->string('type')->toString());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function fieldRules(): array
    {
        return match ($this->documentType()) {
            ClinicalDocumentType::InformedConsent => [
                'procedure' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string', 'max:3000'],
                'risks' => ['required', 'string', 'max:3000'],
                'benefits' => ['nullable', 'string', 'max:2000'],
                'alternatives' => ['nullable', 'string', 'max:2000'],
            ],
            ClinicalDocumentType::SickLeave => [
                'start_date' => ['required', 'date'],
                'days' => ['required', 'integer', 'min:1', 'max:'.self::MAX_SICK_LEAVE_DAYS],
                'origin' => ['required', Rule::enum(SickLeaveOrigin::class)],
                'is_extension' => ['nullable', 'boolean'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            ClinicalDocumentType::Referral => [
                'specialty' => ['required', 'string', 'max:150'],
                'priority' => ['required', Rule::enum(CarePriority::class)],
                'reason' => ['required', 'string', 'max:2000'],
                'clinical_summary' => ['nullable', 'string', 'max:3000'],
            ],
            ClinicalDocumentType::ExamOrder => [
                'exams' => ['required', 'array', 'min:1', 'max:20'],
                'exams.*' => ['nullable', 'string', 'max:150'],
                'priority' => ['required', Rule::enum(CarePriority::class)],
                'indications' => ['nullable', 'string', 'max:1000'],
            ],
            null => [],
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
            'type.required' => 'Selecciona el tipo de documento.',
            'days.max' => 'Una incapacidad no puede superar los '.self::MAX_SICK_LEAVE_DAYS.' días; emite una prórroga para el tiempo adicional.',
            'exams.required' => 'Agrega al menos un examen.',
            'exams.min' => 'Agrega al menos un examen.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'procedure' => 'procedimiento',
            'description' => 'descripción',
            'risks' => 'riesgos',
            'start_date' => 'fecha de inicio',
            'days' => 'días',
            'origin' => 'origen',
            'specialty' => 'especialidad o servicio',
            'priority' => 'prioridad',
            'reason' => 'motivo',
        ];
    }
}
