<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
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
            'amount' => $this->amount,
            'method' => $this->method->toOption(),
            'status' => $this->status->toOption(),
            'concept' => $this->concept,
            'can' => [
                'mark_paid' => $request->user()?->can('markPaid', $this->resource) ?? false,
                'void' => $request->user()?->can('void', $this->resource) ?? false,
                'download_invoice' => $request->user()?->can('downloadInvoice', $this->resource) ?? false,
            ],
            'reference' => $this->reference,
            'paid_at' => $this->paid_at?->format('Y-m-d'),
            'notes' => $this->notes,
            'patient' => $this->whenLoaded('patient', fn (): array => [
                'id' => $this->patient->id,
                'full_name' => $this->patient->full_name,
            ]),
            'appointment' => $this->whenLoaded('appointment', fn (): ?array => $this->appointment ? [
                'id' => $this->appointment->id,
                'scheduled_at' => $this->appointment->scheduled_at->toIso8601String(),
                'reason' => $this->appointment->reason,
                'status' => $this->appointment->status->toOption(),
                'doctor' => $this->appointment->doctor->user->name,
            ] : null),
            'recorded_by' => $this->whenLoaded('recorder', fn (): ?string => $this->recorder?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
