<?php

namespace App\Http\Requests;

use App\Enums\AttachmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Correct (replace with a new file) or void an attachment, always with a reason.
 */
class ChangeAttachmentStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. Only the doctor
     * who manages the consultation's attachments does it, and only once.
     */
    public function authorize(): bool
    {
        $attachment = $this->route('attachment');

        return $attachment->isActive()
            && $this->user()->can('manageAttachments', $attachment->consultation);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_map(fn (AttachmentStatus $status): string => $status->value, AttachmentStatus::closing()))],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'file' => [
                Rule::requiredIf($this->input('status') === AttachmentStatus::Corrected->value),
                'nullable',
                File::types(['pdf', 'jpg', 'jpeg', 'png', 'webp'])->max(10 * 1024),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function status(): AttachmentStatus
    {
        return $this->enum('status', AttachmentStatus::class);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Indica si es una corrección o una anulación.',
            'reason.required' => 'Indica el motivo.',
            'reason.min' => 'Describe el motivo con un poco más de detalle.',
            'file.required' => 'Sube el archivo corregido.',
        ];
    }
}
