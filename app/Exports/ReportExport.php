<?php

namespace App\Exports;

/**
 * Exports an already aggregated report. Unlike the listing exports it is not
 * built from a query, so it does not use the parent constructor.
 *
 * @phpstan-import-type Report from \App\Actions\Reports\BuildReport
 */
class ReportExport extends TableExport
{
    /**
     * @param  Report  $report
     * @param  list<string>  $summary  Human readable filters, shown on the PDF.
     */
    public function __construct(private array $report, private array $summary = []) {}

    public function title(): string
    {
        return $this->report['title'];
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->report['headings'];
    }

    /**
     * @return list<int>
     */
    public function moneyColumns(): array
    {
        return $this->report['money_columns'];
    }

    /**
     * @return iterable<int, list<string|int|float|null>>
     */
    public function rows(): iterable
    {
        yield from $this->report['rows'];

        if ($this->report['totals'] !== []) {
            yield $this->report['totals'];
        }
    }

    /**
     * @return list<string>
     */
    public function filterSummary(): array
    {
        return $this->summary;
    }
}
