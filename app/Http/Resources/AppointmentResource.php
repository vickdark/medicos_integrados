<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
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
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'status' => $this->status->toOption(),
            'reason' => $this->reason,
            'notes' => $this->notes,
            'patient' => $this->whenLoaded('patient', fn (): array => [
                'id' => $this->patient->id,
                'full_name' => $this->patient->full_name,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn (): array => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->user->name,
                'specialty' => $this->doctor->specialty->name,
            ]),
            'consultation_id' => $this->whenLoaded('consultation', fn (): ?int => $this->consultation?->id),
            'can' => [
                'update_status' => $request->user()?->can('updateStatus', $this->resource) ?? false,
            ],
        ];
    }
}
