<?php

namespace App\Models;

use Database\Factories\DiagnosisFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A code of the CIE-10 (ICD-10) classification, as published for Colombia:
 * four characters, without dot, e.g. "J00X" or "E119".
 */
class Diagnosis extends Model
{
    /** @use HasFactory<DiagnosisFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'description',
        'category',
        'chapter',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Codes that can still be assigned to a consultation.
     *
     * @param  Builder<Diagnosis>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Code and description, e.g. "J00X · Rinofaringitis aguda".
     *
     * @return Attribute<string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => "{$this->code} · {$this->description}");
    }

    /**
     * Normalize a code to the four-character format of the catalog: upper case,
     * without dot, and completed with "X" when the category has no subcategory.
     */
    public static function normalizeCode(string $code): string
    {
        $code = strtoupper(str_replace(['.', ' ', '-'], '', trim($code)));

        return strlen($code) === 3 ? $code.'X' : $code;
    }

    /**
     * Match codes starting with the term or descriptions containing every word.
     *
     * @param  Builder<Diagnosis>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $code = self::normalizeCode($term);
        $words = preg_split('/\s+/', trim($term)) ?: [];

        $query->where(function (Builder $query) use ($code, $words): void {
            $query->where('code', 'like', rtrim($code, 'X').'%')
                ->orWhere(function (Builder $query) use ($words): void {
                    foreach ($words as $word) {
                        $query->where('description', 'like', "%{$word}%");
                    }
                });
        });
    }

    /**
     * @return array<string, int|string>
     */
    public function toOption(): array
    {
        return ['value' => $this->id, 'label' => $this->label];
    }
}
