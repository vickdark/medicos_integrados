<?php

use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Header and rows with the layout of the SISPRO reference table export.
 *
 * @return list<list<string>>
 */
function sisproRows(): array
{
    return [
        ['Tabla', 'Codigo', 'Nombre', 'Descripcion', 'Habilitado', 'Aplicacion', 'Extra_I:AplicaASexo', 'Extra_VI:Capitulo'],
        ['CIE10', 'A000', 'COLERA DEBIDO A VIBRIO CHOLERAE 01, BIOTIPO CHOLERAE', 'COLERA', 'SI', '', '3', 'CIERTAS ENFERMEDADES INFECCIOSAS Y PARASITARIAS (A00-B99)'],
        ['CIE10', 'A009', 'COLERA, NO ESPECIFICADO', 'COLERA', 'SI', '', '3', 'CIERTAS ENFERMEDADES INFECCIOSAS Y PARASITARIAS (A00-B99)'],
        ['CIE10', 'I10X', 'HIPERTENSION ESENCIAL (PRIMARIA)', 'HIPERTENSION ESENCIAL (PRIMARIA)', 'SI', '', '3', 'ENFERMEDADES DEL SISTEMA CIRCULATORIO (I00-I99)'],
        ['CIE10', 'U049', 'SINDROME RESPIRATORIO AGUDO GRAVE', 'SRAG', 'NO', '', '3', 'CODIGOS PARA PROPOSITOS ESPECIALES (U00-U99)'],
        ['CIE10', 'A00', 'CODIGO DE TRES CARACTERES', 'COLERA', 'SI', '', '3', ''],
        ['CIE10', '12', 'CODIGO INVALIDO', '', 'SI', '', '3', ''],
        ['CIE10', 'A009', 'COLERA REPETIDO', 'COLERA', 'SI', '', '3', ''],
        ['CIE10', 'B019', '', '', 'SI', '', '3', ''],
    ];
}

/**
 * @param  list<list<string>>  $rows
 */
function writeCie10Csv(array $rows, string $delimiter = ';', ?string $encoding = null): string
{
    $path = tempnam(sys_get_temp_dir(), 'cie10').'.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row, $delimiter, '"', '');
    }

    fclose($handle);

    if ($encoding) {
        file_put_contents($path, mb_convert_encoding((string) file_get_contents($path), $encoding, 'UTF-8'));
    }

    return $path;
}

/**
 * @return list<list<string|int>>
 */
function etlSummary(int $read, int $valid, int $rejected, int $inserted, int $updated, int $unchanged, int $deactivated): array
{
    return [
        ['Filas leídas', $read],
        ['Válidas', $valid],
        ['Rechazadas', $rejected],
        ['Códigos nuevos', $inserted],
        ['Códigos actualizados', $updated],
        ['Sin cambios', $unchanged],
        ['Deshabilitados (ya no están en la tabla)', $deactivated],
    ];
}

it('loads the SISPRO table mapping columns by header and reports the rejected rows', function () {
    $path = writeCie10Csv(sisproRows());

    $this->artisan('diagnoses:import', ['file' => $path])
        ->expectsTable(['Resultado', 'Filas'], etlSummary(8, 5, 3, 5, 0, 0, 0))
        ->expectsOutputToContain('Código con formato inválido (12)')
        ->assertSuccessful();

    $cholera = Diagnosis::query()->where('code', 'A000')->sole();

    expect(Diagnosis::query()->pluck('code')->sort()->values()->all())->toBe(['A000', 'A009', 'A00X', 'I10X', 'U049'])
        ->and($cholera->description)->toBe('COLERA DEBIDO A VIBRIO CHOLERAE 01, BIOTIPO CHOLERAE')
        ->and($cholera->category)->toBe('COLERA')
        ->and($cholera->chapter)->toBe('CIERTAS ENFERMEDADES INFECCIOSAS Y PARASITARIAS (A00-B99)')
        ->and(Diagnosis::query()->where('code', 'A009')->value('description'))->toBe('COLERA, NO ESPECIFICADO')
        ->and(Diagnosis::query()->where('code', 'U049')->sole()->is_active)->toBeFalse();

    $reports = Storage::disk('local')->files('imports');

    expect($reports)->toHaveCount(1)
        ->and(Storage::disk('local')->get($reports[0]))
        ->toContain('Código repetido en el archivo')
        ->toContain('Sin descripción');

    unlink($path);
});

it('updates changed codes, leaves the rest untouched and is idempotent', function () {
    Diagnosis::factory()->create(['code' => 'I10X', 'description' => 'Texto viejo']);
    $path = writeCie10Csv(array_slice(sisproRows(), 0, 4));

    $this->artisan('diagnoses:import', ['file' => $path])
        ->expectsTable(['Resultado', 'Filas'], etlSummary(3, 3, 0, 2, 1, 0, 0));

    $this->artisan('diagnoses:import', ['file' => $path])
        ->expectsTable(['Resultado', 'Filas'], etlSummary(3, 3, 0, 0, 0, 3, 0));

    expect(Diagnosis::query()->where('code', 'I10X')->value('description'))->toBe('HIPERTENSION ESENCIAL (PRIMARIA)');

    unlink($path);
});

it('simulates the load without saving on a dry run', function () {
    $path = writeCie10Csv(sisproRows());

    $this->artisan('diagnoses:import', ['file' => $path, '--dry-run' => true])
        ->expectsOutputToContain('Simulación: no se guardó ningún cambio.')
        ->assertSuccessful();

    expect(Diagnosis::query()->count())->toBe(0);

    unlink($path);
});

it('disables the codes withdrawn from the table without deleting them', function () {
    $withdrawn = Diagnosis::factory()->create(['code' => 'Z999']);
    $path = writeCie10Csv(array_slice(sisproRows(), 0, 3));

    $this->artisan('diagnoses:import', ['file' => $path])->assertSuccessful();
    expect($withdrawn->fresh()->is_active)->toBeTrue();

    $this->artisan('diagnoses:import', ['file' => $path, '--deactivate-missing' => true])
        ->expectsTable(['Resultado', 'Filas'], etlSummary(2, 2, 0, 0, 0, 2, 1));

    expect($withdrawn->fresh())->not->toBeNull()
        ->and($withdrawn->fresh()->is_active)->toBeFalse();

    unlink($path);
});

it('reads Excel files and CSV files saved in Windows-1252 or without header', function () {
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray(array_slice(sisproRows(), 0, 3));
    $xlsx = tempnam(sys_get_temp_dir(), 'cie10').'.xlsx';
    (new Xlsx($spreadsheet))->save($xlsx);

    $this->artisan('diagnoses:import', ['file' => $xlsx])->assertSuccessful();
    expect(Diagnosis::query()->where('code', 'A009')->value('category'))->toBe('COLERA');

    $latin = writeCie10Csv([
        ['J00X', 'Rinofaringitis aguda (resfriado común)'],
        ['E11.9', 'Diabetes mellitus no insulinodependiente sin mención de complicación'],
    ], ',', 'Windows-1252');

    $this->artisan('diagnoses:import', ['file' => $latin])->assertSuccessful();
    expect(Diagnosis::query()->where('code', 'J00X')->value('description'))->toBe('Rinofaringitis aguda (resfriado común)')
        ->and(Diagnosis::query()->where('code', 'E119')->exists())->toBeTrue();

    unlink($xlsx);
    unlink($latin);
});

it('fails clearly when the file cannot be read', function () {
    $this->artisan('diagnoses:import', ['file' => 'no-existe.csv'])
        ->expectsOutputToContain('No se puede leer el archivo')
        ->assertFailed();
});

it('hides disabled codes from the search and rejects them in new consultations', function () {
    $disabled = Diagnosis::factory()->create(['code' => 'U049', 'description' => 'Síndrome respiratorio agudo grave', 'is_active' => false]);
    $appointment = Appointment::factory()->confirmed()->create();

    $this->actingAs(User::factory()->doctor()->create())
        ->getJson(route('diagnoses.search', ['q' => 'U04']))
        ->assertJsonCount(0);

    $this->actingAs($appointment->doctor->user)
        ->post(route('consultations.store', $appointment->patient), [
            'reason' => 'Control',
            'diagnosis' => 'Texto',
            'diagnosis_type' => '1',
            'primary_diagnosis_id' => $disabled->id,
        ])
        ->assertSessionHasErrors('primary_diagnosis_id');
});
