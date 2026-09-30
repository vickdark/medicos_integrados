<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Describes a listing table that can be exported to Excel or PDF.
 */
abstract class TableExport
{
    /**
     * @param  Builder<covariant Model>  $query  The already filtered and authorized query.
     * @param  array<string, string|int|null>  $filters  The active filters, as sent by the page.
     */
    public function __construct(protected Builder $query, protected array $filters = []) {}

    /**
     * Human readable title used for the sheet, the PDF header and the file name.
     */
    abstract public function title(): string;

    /**
     * Column headings, in the same order as each row.
     *
     * @return list<string>
     */
    abstract public function headings(): array;

    /**
     * The rows to export. Implementations should stream them lazily.
     *
     * @return iterable<int, list<string|int|float|null>>
     */
    abstract public function rows(): iterable;

    /**
     * Indexes of the columns that hold monetary amounts.
     *
     * @return list<int>
     */
    public function moneyColumns(): array
    {
        return [];
    }

    /**
     * Human readable description of the active filters, shown on the PDF.
     *
     * @return list<string>
     */
    public function filterSummary(): array
    {
        return array_values(array_filter([
            filled($this->filters['search'] ?? null) ? "Búsqueda: «{$this->filters['search']}»" : null,
            filled($this->filters['from'] ?? null) ? 'Desde: '.$this->formatDate((string) $this->filters['from']) : null,
            filled($this->filters['to'] ?? null) ? 'Hasta: '.$this->formatDate((string) $this->filters['to']) : null,
        ]));
    }

    /**
     * Format a Y-m-d date as d/m/Y.
     */
    protected function formatDate(string $date): string
    {
        return now()->parse($date)->format('d/m/Y');
    }

    /**
     * File name without extension.
     */
    public function filename(): string
    {
        return Str::slug($this->title()).'-'.now()->format('Y-m-d-His');
    }
}
