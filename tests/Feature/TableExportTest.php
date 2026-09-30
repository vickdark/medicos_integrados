<?php

use App\Enums\AuditAction;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Load the streamed .xlsx of a response as a worksheet.
 */
function exportedSheet(TestResponse $response): Worksheet
{
    $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
    file_put_contents($path, $response->streamedContent());

    $sheet = IOFactory::load($path)->getActiveSheet();

    unlink($path);

    return $sheet;
}

it('exports every table to Excel and PDF', function (string $routeName) {
    $admin = User::factory()->admin()->create();
    Appointment::factory()->create();
    Payment::factory()->create();

    $excel = $this->actingAs($admin)->get(route($routeName, ['format' => 'xlsx']));

    $excel->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($excel->headers->get('content-disposition'))->toContain('.xlsx');

    $pdf = $this->actingAs($admin)->get(route($routeName, ['format' => 'pdf']));

    $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($pdf->getContent())->toStartWith('%PDF');
})->with([
    'patients.export',
    'appointments.export',
    'payments.export',
    'doctors.export',
    'specialties.export',
    'audit-logs.export',
]);

it('exports only the rows matching the active filters', function () {
    Patient::factory()->create(['first_name' => 'Rosa', 'last_name' => 'Quispe']);
    Patient::factory()->count(3)->create();

    $response = $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('patients.export', ['format' => 'xlsx', 'search' => 'Quispe']));

    $sheet = exportedSheet($response);

    expect($sheet->getHighestRow())->toBe(2)
        ->and($sheet->getCell('A1')->getValue())->toBe('Documento')
        ->and($sheet->getCell('B2')->getValue())->toBe('Quispe');
});

it('never exports clinical data in the patient directory', function () {
    Patient::factory()->create(['allergies' => 'Penicilina']);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('patients.export', ['format' => 'xlsx']));

    $values = collect(exportedSheet($response)->toArray())->flatten()->filter();

    expect($values)->not->toContain('Penicilina');
});

it('stores text cells as literal strings to prevent formula injection', function () {
    Patient::factory()->create(['last_name' => '=HYPERLINK("http://malicioso.test")']);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('patients.export', ['format' => 'xlsx']));

    $cell = exportedSheet($response)->getCell('B2');

    expect($cell->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($cell->getValue())->toBe('=HYPERLINK("http://malicioso.test")');
});

it('respects the doctor visibility rules when exporting', function () {
    $doctor = Doctor::factory()->create();
    Appointment::factory()->for($doctor)->create();
    Patient::factory()->count(2)->create();

    $response = $this->actingAs($doctor->user)
        ->get(route('patients.export', ['format' => 'xlsx']));

    expect(exportedSheet($response)->getHighestRow())->toBe(2);
});

it('exports to the patient only their own payments with numeric amounts', function () {
    $patient = Patient::factory()->withAccount()->create();
    Payment::factory()->for($patient)->create(['amount' => 80]);
    Payment::factory()->count(2)->create();

    $sheet = exportedSheet(
        $this->actingAs($patient->user)->get(route('payments.export', ['format' => 'xlsx']))
    );

    expect($sheet->getHighestRow())->toBe(2)
        ->and($sheet->getCell('H2')->getValue())->toEqual(80);
});

it('forbids exporting tables the user cannot see', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)->get(route('audit-logs.export', ['format' => 'xlsx']))->assertForbidden();
    $this->actingAs($receptionist)->get(route('specialties.export', ['format' => 'pdf']))->assertForbidden();
    $this->actingAs(Doctor::factory()->create()->user)->get(route('payments.export', ['format' => 'xlsx']))->assertForbidden();
});

it('validates the export format', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('specialties.export', ['format' => 'csv']))
        ->assertSessionHasErrors('format');
});

it('records every export in the audit trail', function () {
    Specialty::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('specialties.export', ['format' => 'xlsx']))->assertOk();

    $log = AuditLog::query()->sole();

    expect($log->action)->toBe(AuditAction::Exported)
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->description)->toContain('Especialidades')
        ->and($log->description)->toContain('Excel');
});
