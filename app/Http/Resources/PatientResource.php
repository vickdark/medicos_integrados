<?php

namespace App\Http\Resources;

use App\Models\Consultation;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Patient
 */
class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canViewMedicalHistory = $request->user()?->can('viewMedicalHistory', $this->resource) ?? false;

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'document_number' => $this->document_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'age' => $this->birth_date?->age,
            'gender' => $this->gender?->toOption(),
            'address' => $this->address,
            'blood_type' => $this->blood_type,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'has_account' => $this->user_id !== null,
            'allergies' => $this->when($canViewMedicalHistory, $this->allergies),
            'chronic_conditions' => $this->when($canViewMedicalHistory, $this->chronic_conditions),
            'medical_background' => $this->when($canViewMedicalHistory, $this->medical_background),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'create_consultation' => $request->user()?->can('create', [Consultation::class, $this->resource]) ?? false,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
