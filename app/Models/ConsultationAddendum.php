<?php

namespace App\Models;

use App\Enums\ConsultationSection;
use Database\Factories\ConsultationAddendumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * A clarifying note added to a consultation. The original record stays as it
 * was written; the note says what part it clarifies, why and who wrote it.
 * Notes are append-only: they cannot be edited or deleted.
 */
class ConsultationAddendum extends Model
{
    /** @use HasFactory<ConsultationAddendumFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'consultation_addenda';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'consultation_id',
        'user_id',
        'author_name',
        'section',
        'reason',
        'content',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => ConsultationSection::class,
            'reason' => 'encrypted',
            'content' => 'encrypted',
        ];
    }

    /**
     * Refuse any change or removal once the note is saved.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Las notas aclaratorias de la historia clínica no se pueden modificar.');
        });

        static::deleting(function (): void {
            throw new LogicException('Las notas aclaratorias de la historia clínica no se pueden eliminar.');
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
