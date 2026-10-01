<?php

use App\Actions\Verification\DocumentVerifier;
use App\Models\ClinicalHistoryExport;
use App\Models\Consultation;
use App\Models\ConsultationAddendum;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('records every clinical history PDF with the consultations it included', function () {
    $patient = Patient::factory()->create();
    $inside = Consultation::factory()->for($patient)->create(['consulted_at' => '2026-03-10 09:00']);
    Consultation::factory()->for($patient)->create(['consulted_at' => '2026-07-01 09:00']);
    $doctor = $inside->doctor->user;

    $this->actingAs($doctor)
        ->get(route('patients.history', ['patient' => $patient, 'from' => '2026-03-01', 'to' => '2026-03-31']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $export = ClinicalHistoryExport::query()->sole();

    expect($export->verification_code)->toStartWith('H')
        ->and($export->consultation_ids)->toBe([$inside->id])
        ->and($export->issued_by)->toBe($doctor->id)
        ->and($export->issued_by_role)->toBe('Médico')
        ->and($export->period_label)->toBe('Del 01/03/2026 al 31/03/2026')
        ->and($export->includes_notes)->toBeTrue();

    $this->actingAs($doctor)->get(route('patients.history', $patient));

    expect(ClinicalHistoryExport::query()->count())->toBe(2);
});

it('verifies a clinical history listing its consultations without clinical content', function () {
    $patient = Patient::factory()->create(['first_name' => 'Ana', 'last_name' => 'Pérez', 'document_number' => '52123456']);
    $consultation = Consultation::factory()->for($patient)->create(['diagnosis' => 'Contenido reservado', 'consulted_at' => '2026-05-04 10:30']);
    ConsultationAddendum::factory()->for($consultation)->create();
    $export = ClinicalHistoryExport::factory()->for($patient)->create([
        'issued_by_name' => 'Dra. Laura Méndez',
        'issued_by_role' => 'Médico',
        'period_label' => 'Historial completo',
        'consultation_ids' => [$consultation->id],
    ]);

    $this->get(route('verification.show', ['code' => $export->verification_code]))
        ->assertOk()
        ->assertDontSee('Contenido reservado')
        ->assertDontSee('52123456')
        ->assertInertia(fn (Assert $page) => $page
            ->where('result.type', 'Historia clínica')
            ->where('result.number', $export->number)
            ->where('result.doctor', null)
            ->where('result.signed', null)
            ->where('result.issued_by', 'Dra. Laura Méndez (Médico)')
            ->where('result.patient.initials', 'A. P.')
            ->where('result.facts.0.value', 'Historial completo')
            ->where('result.facts.1.value', '1')
            ->where('result.facts.3.value.0', fn (string $line) => str_starts_with($line, '04/05/2026 10:30 AM · '.$consultation->doctor->user->name)
                && str_ends_with($line, '1 nota(s) aclaratoria(s)'))
        );
});

it('prints the number and QR on the clinical history PDF', function () {
    $consultation = Consultation::factory()->create();
    $export = ClinicalHistoryExport::factory()->for($consultation->patient)->create(['consultation_ids' => [$consultation->id]]);

    $html = view('pdf.clinical-history', [
        'patient' => $consultation->patient,
        'consultations' => collect([$consultation->load(['doctor.user', 'doctor.specialty', 'prescriptions', 'primaryDiagnosis', 'relatedDiagnoses', 'addenda'])]),
        'period' => 'Historial completo',
        'number' => $export->number,
        'verification' => app(DocumentVerifier::class)->stamp($export->verification_code),
        'includeNotes' => true,
        'generatedAt' => now(),
        'generatedBy' => 'Admin',
    ])->render();

    expect($html)->toContain($export->number)
        ->toContain($export->verification_code)
        ->toContain('Verificación de la historia clínica');
});

it('never lets an issued clinical history record be changed or removed', function () {
    $export = ClinicalHistoryExport::factory()->create();

    expect(fn () => $export->update(['period_label' => 'Otro']))->toThrow(LogicException::class)
        ->and(fn () => $export->delete())->toThrow(LogicException::class);
});

it('leaves the internal notes out of the copy the patient generates and says so when verified', function () {
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);
    $consultation = Consultation::factory()->for($patient)->create(['notes' => 'Nota interna reservada']);

    $this->actingAs($patientUser)
        ->get(route('patients.history', $patient))
        ->assertOk();

    $export = ClinicalHistoryExport::query()->sole();

    expect($export->includes_notes)->toBeFalse()
        ->and($export->issued_by_role)->toBe('Paciente');

    $html = view('pdf.clinical-history', [
        'patient' => $patient,
        'consultations' => collect([$consultation->load(['doctor.user', 'doctor.specialty', 'prescriptions', 'primaryDiagnosis', 'relatedDiagnoses', 'addenda'])]),
        'period' => 'Historial completo',
        'includeNotes' => $export->includes_notes,
        'generatedAt' => now(),
        'generatedBy' => $patientUser->name,
    ])->render();

    expect($html)->not->toContain('Nota interna reservada');

    $this->get(route('verification.show', ['code' => $export->verification_code]))
        ->assertInertia(fn (Assert $page) => $page->where('result.facts.2.value', 'Del paciente (sin notas internas)'));

    $staffExport = ClinicalHistoryExport::factory()->for($patient)->create(['includes_notes' => true]);

    $this->get(route('verification.show', ['code' => $staffExport->verification_code]))
        ->assertInertia(fn (Assert $page) => $page->where('result.facts.2.value', 'Interna, para el personal de la clínica (incluye notas internas)'));
});

it('does not record a history the user cannot see', function () {
    $patient = Patient::factory()->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('patients.history', $patient))
        ->assertForbidden();

    expect(ClinicalHistoryExport::query()->count())->toBe(0);
});
