<?php

namespace App\Http\Resources;

use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Consultation
 */
class ConsultationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consulted_at' => $this->consulted_at->toIso8601String(),
            'reason' => $this->reason,
            'symptoms' => $this->symptoms,
            'diagnosis' => $this->diagnosis,
            'treatment' => $this->treatment,
            'notes' => $this->when((bool) $request->user()?->isStaff(), $this->notes),
            'weight_kg' => $this->weight_kg,
            'height_cm' => $this->height_cm,
            'blood_pressure' => $this->blood_pressure,
            'temperature_c' => $this->temperature_c,
            'heart_rate' => $this->heart_rate,
            'patient' => $this->whenLoaded('patient', fn (): array => [
                'id' => $this->patient->id,
                'full_name' => $this->patient->full_name,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn (): array => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->user->name,
                'specialty' => $this->doctor->specialty->name,
            ]),
            'prescriptions' => PrescriptionResource::collection($this->whenLoaded('prescriptions')),
            'attachments' => ConsultationAttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
