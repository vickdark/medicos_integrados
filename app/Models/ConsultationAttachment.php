<?php

namespace App\Models;

use Database\Factories\ConsultationAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ConsultationAttachment extends Model
{
    /** @use HasFactory<ConsultationAttachmentFactory> */
    use HasFactory;

    /**
     * The disk where clinical attachments are stored. It must never be publicly served.
     */
    public const DISK = 'local';

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
        ];
    }

    /**
     * Remove the stored file when the attachment is deleted.
     */
    protected static function booted(): void
    {
        static::deleted(function (ConsultationAttachment $attachment): void {
            Storage::disk(self::DISK)->delete($attachment->path);
        });
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
}
