<?php

namespace App\Http\Resources;

use App\Models\ConsultationAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConsultationAttachment
 */
class ConsultationAttachmentResource extends JsonResource
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
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'description' => $this->description,
            'status' => $this->status->toOption(),
            'history' => $this->when((bool) $request->user()?->isStaff(), fn (): array => [
                'changed_at' => $this->status_changed_at?->toIso8601String(),
                'changed_by' => $this->status_changed_by_name,
                'reason' => $this->status_reason,
                'replaced_by' => $this->replaced_by_id ? ['id' => $this->replaced_by_id, 'name' => $this->replacedBy?->original_name] : null,
                'replaces' => $this->replaces_id ? ['id' => $this->replaces_id, 'name' => $this->replaces?->original_name] : null,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
