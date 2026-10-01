<?php

use App\Actions\Verification\DocumentVerifier;
use App\Models\ClinicalDocument;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use Inertia\Testing\AssertableInertia as Assert;

it('gives every clinical document a unique verification code', function () {
    [$first, $second] = ClinicalDocument::factory()->count(2)->create();

    expect($first->verification_code)->toMatch('/^D[A-Z2-9]{3}-[A-Z2-9]{4}-[A-Z2-9]{4}$/')
        ->and($first->verification_code)->not->toBe($second->verification_code);
});

it('lets anyone verify a sick leave without exposing the diagnosis or the full patient data', function () {
    $patient = Patient::factory()->create(['first_name' => 'Laura Sofía', 'last_name' => 'Gómez Ruiz', 'document_number' => '1020304050']);
    $consultation = Consultation::factory()->for(Doctor::factory()->signed())->for($patient)->create(['diagnosis' => 'Diagnóstico reservado']);
    $document = ClinicalDocument::factory()->sickLeave(3)->for($consultation)->create();

    $this->get(route('verification.show', ['code' => strtolower(str_replace('-', '', $document->verification_code))]))
        ->assertOk()
        ->assertDontSee('Diagnóstico reservado')
        ->assertDontSee('1020304050')
        ->assertDontSee('Laura Sofía')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Verify')
            ->where('code', $document->verification_code)
            ->where('result.type', 'Incapacidad médica')
            ->where('result.number', $document->number)
            ->where('result.signed', true)
            ->where('result.patient.initials', 'L. S. G. R.')
            ->where('result.patient.document', 'CC ****4050')
            ->where('result.doctor.name', $consultation->doctor->user->name)
            ->where('result.facts.4.label', 'Días')
            ->where('result.facts.4.value', '3')
        );
});

it('verifies prescriptions with their medications and creates the code once', function () {
    $consultation = Consultation::factory()->create();
    Prescription::factory()->create(['consultation_id' => $consultation->id, 'medication' => 'Amoxicilina', 'dosage' => '500 mg']);
    $verifier = app(DocumentVerifier::class);

    $code = $verifier->prescriptionCode($consultation);

    expect($code)->toStartWith('R')
        ->and($verifier->prescriptionCode($consultation->fresh()))->toBe($code);

    $this->get(route('verification.show', ['code' => $code]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('result.type', 'Receta médica')
            ->where('result.signed', false)
            ->where('result.facts.0.label', 'Medicamentos')
            ->where('result.facts.0.value.0', fn (string $line) => str_starts_with($line, 'Amoxicilina · 500 mg'))
        );
});

it('reports unknown codes and redirects a typed code to its result', function () {
    $this->get(route('verification.show', ['code' => 'DAAA-BBBB-CCCC']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('result', null)->where('code', 'DAAA-BBBB-CCCC'));

    $this->get(route('verification.index', ['codigo' => ' d7kq 4m2x p9rt ']))
        ->assertRedirect(route('verification.show', ['code' => 'D7KQ-4M2X-P9RT']));

    $this->get(route('verification.index'))
        ->assertInertia(fn (Assert $page) => $page->component('Verify')->where('result', null));
});

it('prints the QR only on official copies', function () {
    $document = ClinicalDocument::factory()->create();
    $consultation = $document->consultation;

    $official = view('pdf.clinical-document', [
        'document' => $document,
        'consultation' => $consultation,
        'isOfficial' => true,
        'verification' => app(DocumentVerifier::class)->stamp($document->verification_code),
        'generatedAt' => now(),
    ])->render();

    expect($official)->toContain($document->verification_code)
        ->toContain('data:image/png;base64')
        ->toContain('Verificación del documento');

    $copy = view('pdf.clinical-document', [
        'document' => $document,
        'consultation' => $consultation,
        'isOfficial' => false,
        'verification' => null,
        'generatedAt' => now(),
    ])->render();

    expect($copy)->not->toContain($document->verification_code);
});

it('builds the official PDFs with their verification code', function () {
    $document = ClinicalDocument::factory()->create();
    $consultation = Consultation::factory()->create();
    Prescription::factory()->create(['consultation_id' => $consultation->id]);

    $this->actingAs($document->consultation->doctor->user)
        ->get(route('clinical-documents.show', $document))
        ->assertOk();

    $this->actingAs($consultation->doctor->user)
        ->get(route('consultations.prescription', $consultation))
        ->assertOk();

    expect($consultation->fresh()->prescription_verification_code)->toStartWith('R');
});
