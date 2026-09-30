<?php

namespace App\Console\Commands;

use App\Actions\Diagnoses\ImportCie10Catalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ImportDiagnoses extends Command
{
    private const REJECTIONS_SHOWN = 10;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnoses:import
                            {file : Archivo de la tabla CIE-10 (Excel .xlsx/.xls o CSV/TXT)}
                            {--dry-run : Simula la carga y muestra el resultado sin guardar cambios}
                            {--deactivate-missing : Deshabilita los códigos que ya no están en el archivo (usar solo con la tabla completa)}
                            {--delimiter= : Separador de columnas del CSV; si se omite se detecta entre ; , | y tabulador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ETL del catálogo CIE-10: carga o actualiza los códigos desde la tabla de referencia de SISPRO';

    /**
     * Execute the console command.
     */
    public function handle(ImportCie10Catalog $etl): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $report = $etl->run(
                (string) $this->argument('file'),
                $dryRun,
                (bool) $this->option('deactivate-missing'),
                $this->option('delimiter') ?: null,
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('Simulación: no se guardó ningún cambio.');
        }

        $this->table(['Resultado', 'Filas'], [
            ['Filas leídas', $report['read']],
            ['Válidas', $report['valid']],
            ['Rechazadas', $report['rejected']],
            ['Códigos nuevos', $report['inserted']],
            ['Códigos actualizados', $report['updated']],
            ['Sin cambios', $report['unchanged']],
            ['Deshabilitados (ya no están en la tabla)', $report['deactivated']],
        ]);

        if ($report['rejections'] !== []) {
            $this->newLine();
            $this->warn('Filas rechazadas'.($report['rejected'] > self::REJECTIONS_SHOWN ? ' (primeras '.self::REJECTIONS_SHOWN.')' : '').':');
            $this->table(
                ['Línea', 'Código', 'Motivo'],
                array_slice($report['rejections'], 0, self::REJECTIONS_SHOWN),
            );
            $this->line('Detalle completo: '.Storage::disk('local')->path((string) $report['report_path']));
        }

        return self::SUCCESS;
    }
}
