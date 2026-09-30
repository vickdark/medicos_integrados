<?php

namespace App\Actions\Diagnoses;

use App\Models\Diagnosis;
use App\Models\DiagnosisImport;
use Generator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * ETL of the CIE-10 catalog from the reference table published by SISPRO
 * (Excel or CSV), or any file with the code in the first column and the
 * description in the second.
 *
 * - Extract: reads the rows and maps the columns by their header.
 * - Transform: normalizes and validates each row; invalid ones are rejected
 *   with the reason and written to a report.
 * - Load: inserts new codes, updates changed ones and, optionally, disables the
 *   codes that are no longer in the table. Codes are never deleted, because
 *   past consultations may reference them.
 *
 * @phpstan-type Cie10Row array{code: string, description: string, category: string|null, chapter: string|null, is_active: bool}
 * @phpstan-type ImportReport array{
 *     read: int, valid: int, rejected: int, inserted: int, updated: int,
 *     unchanged: int, deactivated: int, dry_run: bool,
 *     rejections: list<array{line: int, code: string, reason: string}>,
 *     report_path: string|null, import_id: int
 * }
 */
class ImportCie10Catalog
{
    private const BATCH_SIZE = 500;

    /**
     * Resources for reading the full official table (about 12,000 codes), which
     * exceeds the defaults of a web request when it comes as Excel.
     */
    private const MEMORY_LIMIT = '512M';

    private const TIME_LIMIT_SECONDS = 300;

    /**
     * Header aliases, after lower-casing, removing accents and the "Extra_N:"
     * prefix SISPRO adds to its extra columns.
     *
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        'code' => ['codigo', 'code', 'cod', 'codigo cie10', 'cie10'],
        'name' => ['nombre', 'name', 'nombre diagnostico'],
        'description' => ['descripcion', 'description'],
        'enabled' => ['habilitado', 'enabled', 'activo', 'estado'],
        'chapter' => ['capitulo', 'chapter'],
    ];

    /**
     * Run the ETL, record the run and describe what it did.
     *
     * @return ImportReport
     */
    public function run(
        string $path,
        bool $dryRun = false,
        bool $deactivateMissing = false,
        ?string $delimiter = null,
        ?string $fileName = null,
        ?int $userId = null,
    ): array {
        if (! is_readable($path)) {
            throw new InvalidArgumentException("No se puede leer el archivo: {$path}");
        }

        $this->raiseLimits();

        $read = 0;
        $valid = [];
        $rejections = [];

        foreach ($this->extract($path, $delimiter) as $line => $values) {
            $read++;
            $row = $this->transform($values);

            if (is_string($row)) {
                $rejections[] = ['line' => $line, 'code' => trim((string) ($values['code'] ?? '')), 'reason' => $row];

                continue;
            }

            if (isset($valid[$row['code']])) {
                $rejections[] = ['line' => $line, 'code' => $row['code'], 'reason' => 'Código repetido en el archivo; se conserva la primera aparición.'];

                continue;
            }

            $valid[$row['code']] = $row;
        }

        $loaded = $this->load($valid, $dryRun, $deactivateMissing);

        $summary = [
            'read' => $read,
            'valid' => count($valid),
            'rejected' => count($rejections),
            ...$loaded,
            'dry_run' => $dryRun,
            'report_path' => $rejections === [] ? null : $this->writeRejections($rejections),
        ];

        $import = DiagnosisImport::query()->create([
            ...$summary,
            'user_id' => $userId,
            'file_name' => $fileName ?? basename($path),
            'deactivate_missing' => $deactivateMissing,
        ]);

        Log::info('ETL CIE-10 ejecutado', $summary + ['file' => $import->file_name, 'import_id' => $import->id]);

        return [...$summary, 'rejections' => $rejections, 'import_id' => $import->id];
    }

    /**
     * Read the file row by row as values keyed by column (code, name, ...).
     * Files without a recognizable header are read by position.
     *
     * @return Generator<int, array<string, string>>
     */
    private function extract(string $path, ?string $delimiter): Generator
    {
        $rows = in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['xlsx', 'xls', 'ods'], true)
            ? $this->spreadsheetRows($path)
            : $this->csvRows($path, $delimiter);

        $map = null;

        foreach ($rows as $line => $cells) {
            $cells = array_map(fn ($cell): string => $this->toUtf8(trim((string) $cell)), $cells);

            if ($map === null) {
                $map = $this->headerMap($cells);

                if ($map !== []) {
                    continue;
                }

                $map = ['code' => 0, 'name' => 1];
            }

            if (implode('', $cells) === '') {
                continue;
            }

            yield $line => array_map(fn (int $index): string => $cells[$index] ?? '', $map);
        }
    }

    /**
     * Normalize and validate a row. Returns the clean row or the rejection reason.
     *
     * @param  array<string, string>  $values
     * @return Cie10Row|string
     */
    private function transform(array $values): array|string
    {
        $code = Diagnosis::normalizeCode($values['code'] ?? '');

        if ($code === '') {
            return 'Sin código.';
        }

        if (! preg_match('/^[A-Z][0-9]{2}[0-9A-Z]$/', $code)) {
            return "Código con formato inválido ({$code}); se esperan cuatro caracteres, p. ej. J00X o E119.";
        }

        $description = $this->clean($values['name'] ?? '') ?? $this->clean($values['description'] ?? '');

        if ($description === null) {
            return 'Sin descripción.';
        }

        $hasName = $this->clean($values['name'] ?? '') !== null;

        return [
            'code' => $code,
            'description' => Str::limit($description, 255, ''),
            'category' => $hasName ? $this->clean($values['description'] ?? '') : null,
            'chapter' => $this->clean($values['chapter'] ?? ''),
            'is_active' => $this->isEnabled($values['enabled'] ?? ''),
        ];
    }

    /**
     * Insert and update in batches, and optionally disable the codes that are
     * no longer in the file.
     *
     * @param  array<string, Cie10Row>  $rows
     * @return array{inserted: int, updated: int, unchanged: int, deactivated: int}
     */
    private function load(array $rows, bool $dryRun, bool $deactivateMissing): array
    {
        $existing = Diagnosis::query()
            ->get(['code', 'description', 'category', 'chapter', 'is_active'])
            ->keyBy('code');

        $inserted = 0;
        $updated = 0;
        $changes = [];

        foreach ($rows as $code => $row) {
            $current = $existing->get($code);

            if ($current === null) {
                $inserted++;
            } elseif ($current->description !== $row['description']
                || $current->category !== $row['category']
                || $current->chapter !== $row['chapter']
                || $current->is_active !== $row['is_active']) {
                $updated++;
            } else {
                continue;
            }

            $changes[] = [...$row, 'created_at' => now(), 'updated_at' => now()];
        }

        $missing = $deactivateMissing
            ? $existing->filter(fn (Diagnosis $diagnosis): bool => $diagnosis->is_active && ! isset($rows[$diagnosis->code]))->keys()
            : collect();

        if (! $dryRun) {
            foreach (array_chunk($changes, self::BATCH_SIZE) as $batch) {
                Diagnosis::query()->upsert($batch, ['code'], ['description', 'category', 'chapter', 'is_active', 'updated_at']);
            }

            foreach ($missing->chunk(self::BATCH_SIZE) as $codes) {
                Diagnosis::query()->whereIn('code', $codes->all())->update(['is_active' => false]);
            }
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'unchanged' => count($rows) - $inserted - $updated,
            'deactivated' => $missing->count(),
        ];
    }

    /**
     * @return Generator<int, list<mixed>>
     */
    private function spreadsheetRows(string $path): Generator
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];

            foreach ($row->getCellIterator() as $cell) {
                $cells[] = $cell->getValue();
            }

            yield $row->getRowIndex() => $cells;
        }
    }

    /**
     * @return Generator<int, list<string|null>>
     */
    private function csvRows(string $path, ?string $delimiter): Generator
    {
        $handle = fopen($path, 'r');
        $delimiter ??= $this->detectDelimiter((string) fgets($handle));
        rewind($handle);
        $line = 0;

        while (($cells = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            yield ++$line => $cells;
        }

        fclose($handle);
    }

    /**
     * Column positions by field, or an empty array when the row is not a header.
     *
     * @param  list<string>  $cells
     * @return array<string, int>
     */
    private function headerMap(array $cells): array
    {
        $map = [];

        foreach ($cells as $index => $cell) {
            $header = Str::of($cell)->ascii()->lower()->replaceMatches('/^extra_[ivx]+:/', '')->trim()->toString();

            foreach (self::COLUMNS as $field => $aliases) {
                if (! isset($map[$field]) && in_array($header, $aliases, true)) {
                    $map[$field] = $index;
                }
            }
        }

        return isset($map['code']) && (isset($map['name']) || isset($map['description'])) ? $map : [];
    }

    private function isEnabled(string $value): bool
    {
        $value = Str::of($value)->ascii()->lower()->trim()->toString();

        return ! in_array($value, ['no', 'n', '0', 'false', 'falso', 'inactivo', 'deshabilitado', 'disabled'], true);
    }

    private function clean(string $value): ?string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        return $value === '' ? null : $value;
    }

    private function detectDelimiter(string $line): string
    {
        $counts = [';' => substr_count($line, ';'), ',' => substr_count($line, ','), "\t" => substr_count($line, "\t"), '|' => substr_count($line, '|')];
        arsort($counts);

        return (string) array_key_first($counts);
    }

    /**
     * Official tables are often saved in Windows-1252; convert them to UTF-8.
     */
    private function toUtf8(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;

        return mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }

    /**
     * Save the rejected rows so they can be reviewed and fixed.
     *
     * @param  list<array{line: int, code: string, reason: string}>  $rejections
     */
    private function writeRejections(array $rejections): string
    {
        $path = 'imports/cie10-rechazos-'.now()->format('Ymd-His').'.csv';
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['linea', 'codigo', 'motivo'], ';', '"', '');

        foreach ($rejections as $rejection) {
            fputcsv($handle, [$rejection['line'], $rejection['code'], $rejection['reason']], ';', '"', '');
        }

        rewind($handle);
        Storage::disk('local')->put($path, (string) stream_get_contents($handle));
        fclose($handle);

        return $path;
    }

    /**
     * Raise memory and time limits for this run, never lowering them.
     */
    private function raiseLimits(): void
    {
        $current = ini_get('memory_limit');

        if ($current !== '-1' && $this->toBytes((string) $current) < $this->toBytes(self::MEMORY_LIMIT)) {
            ini_set('memory_limit', self::MEMORY_LIMIT);
        }

        $timeLimit = (int) ini_get('max_execution_time');

        if ($timeLimit !== 0 && $timeLimit < self::TIME_LIMIT_SECONDS) {
            set_time_limit(self::TIME_LIMIT_SECONDS);
        }
    }

    private function toBytes(string $value): int
    {
        $number = (int) $value;

        return match (strtoupper(substr(trim($value), -1))) {
            'G' => $number * 1024 ** 3,
            'M' => $number * 1024 ** 2,
            'K' => $number * 1024,
            default => $number,
        };
    }
}
