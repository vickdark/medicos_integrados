<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->toOption(),
            'profile' => match (true) {
                $this->doctor !== null => "{$this->doctor->specialty->name} · {$this->doctor->license_number}",
                $this->patient !== null => $this->patient->document_number ? "Doc. {$this->patient->document_number}" : null,
                default => null,
            },
            'is_active' => $this->is_active,
            'is_self' => $this->is($request->user()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
