<?php

use App\Enums\ClinicalDocumentType;
use App\Models\AuditLog;
use App\Models\ClinicalDocument;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

function issue(Consultation $consultation, array $payload): TestResponse
{
    return test()->actingAs($consultation->doctor->user)
        ->post(route('consultations.documents.store', $consultation), $payload);
}

it('issues each type of clinical document with its own data', function (array $payload, string $summary) {
    $consultation = Consultation::factory()->create();

    issue($consultation, $payload)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $document = ClinicalDocument::query()->sole();

    expect($document->type->value)->toBe($payload['type'])
        ->and($document->issued_by)->toBe($consultation->doctor->user_id)
        ->and($document->summary())->toBe($summary)
        ->and($document->number)->toStartWith($document->type->prefix().'-')
        ->and(AuditLog::query()->where('description', 'like', 'Emitió%')->exists())->toBeTrue();
})->with([
    'consentimiento' => [[
        'type' => 'consent',
        'procedure' => 'Infiltración de rodilla derecha',
        'description' => 'Aplicación de corticoide intraarticular.',
        'risks' => 'Dolor local, infección, reacción alérgica.',
    ], 'Infiltración de rodilla derecha'],
    'incapacidad' => [[
        'type' => 'sick_leave',
        'start_date' => '2026-10-01',
        'days' => 3,
        'origin' => 'general_illness',
    ], '3 días, del 01/10/2026 al 03/10/2026'],
    'remisión' => [[
        'type' => 'referral',
        'specialty' => 'Cardiología',
        'priority' => 'priority',
        'reason' => 'Soplo sistólico en estudio.',
    ], 'A Cardiología'],
    'orden de exámenes' => [[
        'type' => 'exam_order',
        'exams' => ['Hemograma completo', ' Glicemia en ayunas ', ''],
        'priority' => 'routine',
    ], 'Hemograma completo, Glicemia en ayunas'],
]);

it('validates the fields of the selected type', function () {
    $consultation = Consultation::factory()->create();

    issue($consultation, ['type' => 'consent'])->assertSessionHasErrors(['procedure', 'description', 'risks']);
    issue($consultation, ['type' => 'sick_leave', 'start_date' => 'x', 'days' => 45, 'origin' => 'nope'])
        ->assertSessionHasErrors(['start_date', 'days', 'origin']);
    issue($consultation, ['type' => 'referral', 'priority' => 'someday'])->assertSessionHasErrors(['specialty', 'priority', 'reason']);
    issue($consultation, ['type' => 'exam_order', 'exams' => [], 'priority' => 'routine'])->assertSessionHasErrors('exams');
    issue($consultation, ['type' => 'certificate'])->assertSessionHasErrors('type');

    expect(ClinicalDocument::query()->count())->toBe(0);
});

it('only lets the doctor who recorded the consultation issue documents', function () {
    $consultation = Consultation::factory()->create();
    $payload = ['type' => 'exam_order', 'exams' => ['Hemograma'], 'priority' => 'routine'];

    foreach ([User::factory()->admin()->create(), User::factory()->receptionist()->create(), Doctor::factory()->create()->user] as $user) {
        $this->actingAs($user)
            ->post(route('consultations.documents.store', $consultation), $payload)
            ->assertForbidden();
    }

    expect(ClinicalDocument::query()->count())->toBe(0);
});

it('lists the documents on the consultation and opens the PDF', function () {
    $consultation = Consultation::factory()->create();
    $document = ClinicalDocument::factory()->sickLeave(5)->for($consultation)->create();

    $this->actingAs($consultation->doctor->user)
        ->get(route('consultations.show', $consultation))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.issue_documents', true)
            ->where('consultation.documents.0.number', $document->number)
            ->where('consultation.documents.0.type.label', 'Incapacidad médica')
            ->has('documentOptions.types', 4)
        );

    $this->actingAs($consultation->doctor->user)
        ->get(route('clinical-documents.show', $document))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect(AuditLog::query()->where('description', "Generó Incapacidad médica {$document->number} en PDF")->exists())->toBeTrue();
});

it('gives the patient a copy marked as not valid and keeps others out', function () {
    $consultation = Consultation::factory()->create();
    $document = ClinicalDocument::factory()->for($consultation)->create();
    $patientUser = User::factory()->patient()->create();
    $consultation->patient->update(['user_id' => $patientUser->id]);

    $this->actingAs($patientUser)
        ->get(route('clinical-documents.show', $document))
        ->assertOk();

    expect(AuditLog::query()->where('description', 'like', '%(copia sin validez)')->exists())->toBeTrue();

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('clinical-documents.show', $document))
        ->assertForbidden();

    $this->actingAs(User::factory()->patient()->create())
        ->get(route('clinical-documents.show', $document))
        ->assertForbidden();
});

it('never lets an issued document be changed or removed', function () {
    $document = ClinicalDocument::factory()->create();

    expect(fn () => $document->update(['data' => ['exams' => ['Otro']]]))->toThrow(LogicException::class)
        ->and(fn () => $document->delete())->toThrow(LogicException::class)
        ->and($document->fresh()->type)->toBe(ClinicalDocumentType::ExamOrder);
});
