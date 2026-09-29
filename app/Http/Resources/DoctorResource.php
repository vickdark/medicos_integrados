<?php

namespace App\Http\Resources;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Doctor
 */
class DoctorResource extends JsonResource
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
            'name' => $this->user->name,
            'email' => $this->user->email,
            'specialty' => $this->specialty->name,
            'license_number' => $this->license_number,
            'phone' => $this->phone,
            'consultation_fee' => $this->consultation_fee,
            'bio' => $this->bio,
        ];
    }
}
