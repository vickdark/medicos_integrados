<?php

namespace App\Models;

use Database\Factories\DiagnosisImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A run of the CIE-10 catalog ETL, from the interface or the console.
 */
class DiagnosisImport extends Model
{
    /** @use HasFactory<DiagnosisImportFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'file_name',
        'dry_run',
        'deactivate_missing',
        'read',
        'valid',
        'rejected',
        'inserted',
        'updated',
        'unchanged',
        'deactivated',
        'report_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dry_run' => 'boolean',
            'deactivate_missing' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the report of rejected rows is still stored.
     */
    public function hasRejectionReport(): bool
    {
        return $this->report_path !== null && Storage::disk('local')->exists($this->report_path);
    }

    /**
     * The first rejected rows, read from the stored report.
     *
     * @return list<array{line: string, code: string, reason: string}>
     */
    public function rejectionSample(int $limit = 20): array
    {
        if (! $this->hasRejectionReport()) {
            return [];
        }

        $lines = preg_split('/\r\n|\n/', (string) Storage::disk('local')->get($this->report_path)) ?: [];
        $sample = [];

        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '' || count($sample) === $limit) {
                continue;
            }

            [$number, $code, $reason] = array_pad(str_getcsv($line, ';', '"', ''), 3, '');
            $sample[] = ['line' => $number, 'code' => $code, 'reason' => $reason];
        }

        return $sample;
    }
}
