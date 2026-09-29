<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuditLog
 */
class AuditLogResource extends JsonResource
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
            'action' => $this->action->toOption(),
            'description' => $this->description,
            'user' => $this->user ? [
                'name' => $this->user->name,
                'role' => $this->user->role->label(),
            ] : null,
            'patient' => $this->patient ? [
                'id' => $this->patient->id,
                'full_name' => $this->patient->full_name,
            ] : null,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
