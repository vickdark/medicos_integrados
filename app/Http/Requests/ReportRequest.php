<?php

namespace App\Http\Requests;

use App\Enums\ReportGroup;
use App\Enums\ReportType;
use App\Enums\UserRole;
use App\Models\Doctor;
use App\Models\Specialty;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('view-reports');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(ReportType::class)->when($this->isDoctor(), fn ($rule) => $rule->except(ReportType::Income))],
            'group' => ['nullable', Rule::enum(ReportGroup::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'doctor_id' => ['nullable', Rule::exists(Doctor::class, 'id')],
            'specialty_id' => ['nullable', Rule::exists(Specialty::class, 'id')],
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
            'to.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
        ];
    }

    public function reportType(): ReportType
    {
        return $this->enum('type', ReportType::class) ?? ReportType::Appointments;
    }

    public function reportGroup(): ReportGroup
    {
        return $this->isDoctor()
            ? ReportGroup::Doctor
            : ($this->enum('group', ReportGroup::class) ?? ReportGroup::Doctor);
    }

    /**
     * Doctors only see their own figures, and never the income.
     */
    public function isDoctor(): bool
    {
        return $this->user()?->hasRole(UserRole::Doctor) ?? false;
    }

    /**
     * The requested period. It defaults to the current month up to today.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function period(): array
    {
        $today = CarbonImmutable::today();

        return [
            $this->filled('from') ? CarbonImmutable::parse($this->string('from')->toString()) : $today->startOfMonth(),
            $this->filled('to') ? CarbonImmutable::parse($this->string('to')->toString()) : $today,
        ];
    }

    /**
     * The filters as the page shows them, with defaults applied.
     *
     * @return array{type: string, group: string, from: string, to: string, doctor_id: int|null, specialty_id: int|null}
     */
    public function filters(): array
    {
        [$from, $to] = $this->period();

        return [
            'type' => $this->reportType()->value,
            'group' => $this->reportGroup()->value,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'doctor_id' => $this->isDoctor() ? $this->user()->doctor->id : ($this->integer('doctor_id') ?: null),
            'specialty_id' => $this->isDoctor() ? null : ($this->integer('specialty_id') ?: null),
        ];
    }
}
