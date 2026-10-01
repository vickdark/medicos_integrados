<?php

namespace App\Models;

use App\Enums\AttachmentStatus;
use Database\Factories\ConsultationAttachmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * A file of the clinical record (lab result, image…). It is never deleted: a
 * wrong file is either corrected (replaced by a new version, keeping the old
 * one) or voided, always recording who did it, when and why.
 */
class ConsultationAttachment extends Model
{
    /** @use HasFactory<ConsultationAttachmentFactory> */
    use HasFactory;

    /**
     * The disk where clinical attachments are stored. It must never be publicly served.
     */
    public const DISK = 'local';

    /**
     * Fields that may change once the attachment is stored: only those that close it.
     *
     * @var list<string>
     */
    private const CLOSING_FIELDS = ['status', 'replaced_by_id', 'status_changed_at', 'status_changed_by', 'status_changed_by_name', 'status_reason', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'consultation_id',
        'uploaded_by',
        'original_name',
        'path',
        'mime_type',
        'size',
        'description',
        'status',
        'replaced_by_id',
        'replaces_id',
        'status_changed_at',
        'status_changed_by',
        'status_changed_by_name',
        'status_reason',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'status' => AttachmentStatus::class,
            'status_changed_at' => 'datetime',
            'status_reason' => 'encrypted',
        ];
    }

    /**
     * Attachments are never deleted and, once stored, the only change allowed is
     * closing an active one (correcting or voiding it) a single time.
     */
    protected static function booted(): void
    {
        static::updating(function (ConsultationAttachment $attachment): void {
            $wasActive = $attachment->getOriginal('status') === AttachmentStatus::Active;
            $changesOtherFields = array_diff(array_keys($attachment->getDirty()), self::CLOSING_FIELDS) !== [];

            if (! $wasActive || $changesOtherFields) {
                throw new LogicException('Un archivo adjunto de la historia clínica solo se puede corregir o anular una vez y no se puede modificar.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Un archivo adjunto de la historia clínica no se puede eliminar; corrígelo o anúlalo indicando el motivo.');
        });
    }

    /**
     * Whether the attachment is the current version shown to the patient.
     */
    public function isActive(): bool
    {
        return $this->status === AttachmentStatus::Active;
    }

    /**
     * @param  Builder<ConsultationAttachment>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', AttachmentStatus::Active);
    }

    /**
     * Void the attachment keeping the file, with who did it and why.
     */
    public function void(User $user, string $reason): void
    {
        $this->close(AttachmentStatus::Voided, $user, $reason);
    }

    /**
     * Replace the attachment with a corrected file. The original is kept, marked
     * as corrected and linked to the new version, which becomes the active one.
     */
    public function correctWith(UploadedFile $file, User $user, string $reason, ?string $description = null): self
    {
        return DB::transaction(function () use ($file, $user, $reason, $description): self {
            $replacement = $this->consultation->attachments()->create([
                'uploaded_by' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $file->store("consultations/{$this->consultation_id}", self::DISK),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'description' => $description ?? $this->description,
                'replaces_id' => $this->id,
            ]);

            $this->close(AttachmentStatus::Corrected, $user, $reason, $replacement);

            return $replacement;
        });
    }

    private function close(AttachmentStatus $status, User $user, string $reason, ?self $replacement = null): void
    {
        $this->update([
            'status' => $status,
            'replaced_by_id' => $replacement?->id,
            'status_changed_at' => now(),
            'status_changed_by' => $user->id,
            'status_changed_by_name' => $user->name,
            'status_reason' => $reason,
        ]);
    }

    /**
     * @return BelongsTo<Consultation, $this>
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * The corrected version that replaced this file.
     *
     * @return BelongsTo<ConsultationAttachment, $this>
     */
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_id');
    }

    /**
     * The file this one corrects.
     *
     * @return BelongsTo<ConsultationAttachment, $this>
     */
    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_id');
    }
}
