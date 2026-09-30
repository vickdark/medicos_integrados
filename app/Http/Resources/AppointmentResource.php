<?php

namespace App\Http\Resources;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Payment;
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
            'pending_payment_id' => $this->pending_payment_id ?? null,
            'payment_status' => match (true) {
                (bool) ($this->has_paid_payment ?? false) => ['value' => 'paid', 'label' => 'Pagada'],
                (bool) ($this->has_pending_payment ?? false) => ['value' => 'pending', 'label' => 'Pago pendiente'],
                default => null,
            },
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
                'view_consultation' => $this->resource->relationLoaded('consultation')
                    && $this->consultation !== null
                    && ($request->user()?->can('view', $this->consultation) ?? false),
                'reschedule' => $request->user()?->can('reschedule', $this->resource) ?? false,
                'update_status' => $request->user()?->can('updateStatus', $this->resource) ?? false,
                'settle_payment' => ($this->pending_payment_id ?? null) !== null
                    && ($request->user()?->can('create', Payment::class) ?? false),
                'register_payment' => $this->status === AppointmentStatus::Confirmed || $this->status === AppointmentStatus::Completed
                    ? ! ($this->has_paid_payment ?? false) && ! ($this->has_pending_payment ?? false) && ($request->user()?->can('create', Payment::class) ?? false)
                    : false,
            ],
        ];
    }
}
