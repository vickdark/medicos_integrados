<?php

use App\Models\AuditLog;
use App\Models\Diagnosis;
use App\Models\DiagnosisImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
});

function cie10Upload(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('CIE10.csv', implode("\n", [
        'Tabla;Codigo;Nombre;Descripcion;Habilitado;Extra_VI:Capitulo',
        'CIE10;A000;COLERA DEBIDO A VIBRIO CHOLERAE 01, BIOTIPO CHOLERAE;COLERA;SI;CAPITULO I',
        'CIE10;I10X;HIPERTENSION ESENCIAL (PRIMARIA);HIPERTENSION ESENCIAL;SI;CAPITULO IX',
        'CIE10;12;CODIGO INVALIDO;;SI;',
    ]));
}

it('shows the catalog status, history and codes to the administrator only', function () {
    Diagnosis::factory()->create(['code' => 'J00X', 'description' => 'Rinofaringitis aguda']);
    Diagnosis::factory()->create(['code' => 'U049', 'is_active' => false]);
    DiagnosisImport::factory()->create(['dry_run' => false]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('cie10.index', ['search' => 'rinofaringitis']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cie10/Index')
            ->where('stats.active', 1)
            ->where('stats.inactive', 1)
            ->whereNot('stats.last_load_at', null)
            ->has('imports', 1)
            ->where('imports.0.user', null)
            ->has('codes.data', 1)
            ->where('codes.data.0.code', 'J00X')
            ->where('latest', null)
        );

    foreach (['receptionist', 'doctor', 'patient'] as $role) {
        $this->actingAs(User::factory()->{$role}()->create())->get(route('cie10.index'))->assertForbidden();
    }
});

it('simulates the load from the interface without saving codes', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->post(route('cie10.imports.store'), ['file' => cie10Upload(), 'dry_run' => '1'])
        ->assertRedirect(route('cie10.index'))
        ->assertSessionHasNoErrors();

    $import = DiagnosisImport::query()->sole();

    expect(Diagnosis::query()->count())->toBe(0)
        ->and($import->dry_run)->toBeTrue()
        ->and($import->user_id)->toBe($admin->id)
        ->and($import->file_name)->toBe('CIE10.csv')
        ->and($import->inserted)->toBe(2)
        ->and($import->rejected)->toBe(1)
        ->and(Storage::disk('local')->files('imports/cie10-uploads'))->toBe([]);

    $this->actingAs($admin)
        ->withSession(['cie10_import_id' => $import->id])
        ->get(route('cie10.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('latest.id', $import->id)
            ->where('latest.dry_run', true)
            ->where('latest.counts.inserted', 2)
            ->where('latest.rejection_sample.0.code', '12')
        );
});

it('loads the codes from the interface and records it in the audit log', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('cie10.imports.store'), ['file' => cie10Upload(), 'deactivate_missing' => '1'])
        ->assertSessionHas('success', 'Catálogo CIE-10 actualizado.');

    expect(Diagnosis::query()->pluck('code')->sort()->values()->all())->toBe(['A000', 'I10X'])
        ->and(DiagnosisImport::query()->sole()->deactivate_missing)->toBeTrue()
        ->and(AuditLog::query()->where('description', 'like', 'Cargó el catálogo CIE-10%')->exists())->toBeTrue();
});

it('downloads the rejected rows of a run', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('cie10.imports.store'), ['file' => cie10Upload(), 'dry_run' => '1']);
    $import = DiagnosisImport::query()->sole();

    $this->actingAs($admin)
        ->get(route('cie10.imports.rejections', $import))
        ->assertOk()
        ->assertDownload();

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('cie10.imports.rejections', $import))
        ->assertForbidden();

    $clean = DiagnosisImport::factory()->create();
    $this->actingAs($admin)->get(route('cie10.imports.rejections', $clean))->assertNotFound();
});

it('validates the uploaded file and keeps others from loading codes', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('cie10.imports.store'), ['file' => UploadedFile::fake()->create('tabla.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('file');

    $this->actingAs($admin)->post(route('cie10.imports.store'), [])->assertSessionHasErrors('file');

    $this->actingAs(User::factory()->doctor()->create())
        ->post(route('cie10.imports.store'), ['file' => cie10Upload()])
        ->assertForbidden();

    expect(DiagnosisImport::query()->count())->toBe(0);
});

it('records the runs made from the console too', function () {
    $path = tempnam(sys_get_temp_dir(), 'cie10').'.csv';
    file_put_contents($path, "J00X;Rinofaringitis aguda\n");

    $this->artisan('diagnoses:import', ['file' => $path])->assertSuccessful();

    $import = DiagnosisImport::query()->sole();

    expect($import->user_id)->toBeNull()
        ->and($import->inserted)->toBe(1);

    unlink($path);
});
