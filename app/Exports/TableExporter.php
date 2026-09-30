<?php

namespace App\Exports;

use App\Actions\Branding\BrandPalette;
use App\Enums\AuditAction;
use App\Enums\ExportFormat;
use App\Models\AppSetting;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class TableExporter
{
    /**
     * Maximum rows rendered in a PDF. Larger exports should use Excel.
     */
    public const PDF_ROW_LIMIT = 1000;

    /**
     * Build the file for the given export and return it as a download.
     */
    public function download(TableExport $export, ExportFormat $format): Response
    {
        AuditLog::record(AuditAction::Exported, null, "Exportó «{$export->title()}» en {$format->label()}");

        return match ($format) {
            ExportFormat::Xlsx => $this->toSpreadsheet($export),
            ExportFormat::Pdf => $this->toPdf($export),
        };
    }

    /**
     * Write the export as an .xlsx file. Every text value is stored as a literal
     * string so cell contents are never evaluated as formulas.
     */
    private function toSpreadsheet(TableExport $export): Response
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($export->title(), 0, 31));

        $headings = $export->headings();
        $lastColumn = count($headings);
        $moneyColumns = $export->moneyColumns();

        foreach ($headings as $index => $heading) {
            $sheet->setCellValueExplicit([$index + 1, 1], $heading, DataType::TYPE_STRING);
        }

        $rowNumber = 2;

        foreach ($export->rows() as $row) {
            foreach (array_values($row) as $index => $value) {
                if (is_int($value) || is_float($value)) {
                    $sheet->setCellValue([$index + 1, $rowNumber], $value);
                } else {
                    $sheet->setCellValueExplicit([$index + 1, $rowNumber], (string) ($value ?? ''), DataType::TYPE_STRING);
                }
            }

            $rowNumber++;
        }

        $lastRow = max($rowNumber - 1, 1);

        $sheet->getStyle([1, 1, $lastColumn, 1])->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => BrandPalette::withoutHash(AppSetting::brandColor())]],
        ]);

        foreach ($moneyColumns as $index) {
            $sheet->getStyle([$index + 1, 2, $index + 1, $lastRow])
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        }

        foreach (range(1, $lastColumn) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter([1, 1, $lastColumn, $lastRow]);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $export->filename().'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * Render the export as a landscape PDF table.
     */
    private function toPdf(TableExport $export): Response
    {
        $rows = [];
        $isTruncated = false;

        foreach ($export->rows() as $row) {
            if (count($rows) === self::PDF_ROW_LIMIT) {
                $isTruncated = true;

                break;
            }

            $rows[] = array_values($row);
        }

        return Pdf::loadView('exports.table', [
            'title' => $export->title(),
            'headings' => $export->headings(),
            'rows' => $rows,
            'moneyColumns' => $export->moneyColumns(),
            'filters' => $export->filterSummary(),
            'isTruncated' => $isTruncated,
            'rowLimit' => self::PDF_ROW_LIMIT,
            'generatedAt' => now(),
            'generatedBy' => request()->user()?->name,
        ])
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4', 'landscape')
            ->download($export->filename().'.pdf');
    }
}
