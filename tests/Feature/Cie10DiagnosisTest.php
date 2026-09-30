<?php

use App\Enums\AffiliationType;
use App\Enums\DiagnosisType;
use App\Enums\DocumentType;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Diagnosis;
use App\Models\Insurer;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function consultationPayload(array $overrides = []): array
{
    return [
        'reason' => 'Dolor de garganta',
        'diagnosis' => 'Faringe eritematosa sin exudado',
        'diagnosis_type' => '2',
        ...$overrides,
    ];
}

it('records the coded main diagnosis and the related ones in order', function () {
    $appointment = Appointment::factory()->confirmed()->create();
    $main = Diagnosis::factory()->create(['code' => 'J029', 'description' => 'Faringitis aguda, no especificada']);
    $first = Diagnosis::factory()->create(['code' => 'R509']);
    $second = Diagnosis::factory()->create(['code' => 'R05X']);

    $this->actingAs($appointment->doctor->user)
        ->post(route('consultations.store', $appointment->patient), consultationPayload([
            'appointment_id' => $appointment->id,
            'primary_diagnosis_id' => $main->id,
            'related_diagnosis_ids' => [$second->id, $first->id],
        ]))
        ->assertSessionHasNoErrors();

    $consultation = Consultation::query()->sole();

    expect($consultation->primaryDiagnosis->code)->toBe('J029')
        ->and($consultation->diagnosis_type)->toBe(DiagnosisType::ConfirmedNew)
        ->and($consultation->relatedDiagnoses->pluck('code')->all())->toBe(['R05X', 'R509']);

    $this->actingAs($appointment->doctor->user)
        ->get(route('consultations.show', $consultation))
        ->assertInertia(fn (Assert $page) => $page
            ->where('consultation.primary_diagnosis.code', 'J029')
            ->where('consultation.diagnosis_type.label', 'Confirmado nuevo')
            ->where('consultation.related_diagnoses.0.code', 'R05X')
        );
});

it('requires a valid main diagnosis and at most three distinct related ones', function () {
    $appointment = Appointment::factory()->confirmed()->create();
    $main = Diagnosis::factory()->create();
    $related = Diagnosis::factory()->count(4)->create();
    $doctor = $appointment->doctor->user;
    $patient = $appointment->patient;

    $this->actingAs($doctor)
        ->post(route('consultations.store', $patient), consultationPayload(['diagnosis_type' => null]))
        ->assertSessionHasErrors(['primary_diagnosis_id', 'diagnosis_type']);

    $this->actingAs($doctor)
        ->post(route('consultations.store', $patient), consultationPayload([
            'primary_diagnosis_id' => $main->id,
            'related_diagnosis_ids' => $related->pluck('id')->all(),
        ]))
        ->assertSessionHasErrors('related_diagnosis_ids');

    $this->actingAs($doctor)
        ->post(route('consultations.store', $patient), consultationPayload([
            'primary_diagnosis_id' => $main->id,
            'related_diagnosis_ids' => [$main->id, $related[0]->id, $related[0]->id],
        ]))
        ->assertSessionHasErrors(['related_diagnosis_ids.0', 'related_diagnosis_ids.1']);

    expect(Consultation::query()->count())->toBe(0);
});

it('searches the CIE-10 catalog by code or description for the staff', function () {
    Diagnosis::factory()->create(['code' => 'J00X', 'description' => 'Rinofaringitis aguda (resfriado común)']);
    Diagnosis::factory()->create(['code' => 'J029', 'description' => 'Faringitis aguda, no especificada']);
    Diagnosis::factory()->create(['code' => 'I10X', 'description' => 'Hipertensión esencial (primaria)']);
    $doctor = User::factory()->doctor()->create();

    $this->actingAs($doctor)->getJson(route('diagnoses.search', ['q' => 'j00']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.label', 'J00X · Rinofaringitis aguda (resfriado común)');

    $this->actingAs($doctor)->getJson(route('diagnoses.search', ['q' => 'aguda faringitis']))
        ->assertJsonCount(2)
        ->assertJsonPath('0.label', 'J00X · Rinofaringitis aguda (resfriado común)')
        ->assertJsonPath('1.label', 'J029 · Faringitis aguda, no especificada');

    $this->actingAs($doctor)->getJson(route('diagnoses.search', ['q' => 'esencial']))
        ->assertJsonCount(1)
        ->assertJsonPath('0.value', Diagnosis::query()->where('code', 'I10X')->value('id'));

    $this->actingAs($doctor)->getJson(route('diagnoses.search', ['q' => 'i']))->assertJsonCount(0);
    $this->actingAs(User::factory()->patient()->create())
        ->getJson(route('diagnoses.search', ['q' => 'j00']))
        ->assertForbidden();
});

it('normalizes CIE-10 codes to the four-character format', function (string $input, string $expected) {
    expect(Diagnosis::normalizeCode($input))->toBe($expected);
})->with([
    ['J00', 'J00X'],
    ['e11.9', 'E119'],
    [' I10 ', 'I10X'],
    ['R05X', 'R05X'],
]);

it('stores the document type, insurer and affiliation of the patient', function () {
    $insurer = Insurer::factory()->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->post(route('patients.store'), [
            'first_name' => 'Laura',
            'last_name' => 'Gómez',
            'document_type' => 'CC',
            'document_number' => '1020304050',
            'insurer_id' => $insurer->id,
            'affiliation_type' => '01',
        ])
        ->assertSessionHasNoErrors();

    $patient = Patient::query()->where('document_number', '1020304050')->sole();

    expect($patient->document_type)->toBe(DocumentType::CitizenshipCard)
        ->and($patient->insurer->is($insurer))->toBeTrue()
        ->and($patient->affiliation_type)->toBe(AffiliationType::ContributoryContributor);
});

it('asks for the document type when the document number is given', function () {
    $this->actingAs(User::factory()->receptionist()->create())
        ->post(route('patients.store'), [
            'first_name' => 'Laura',
            'last_name' => 'Gómez',
            'document_number' => '1020304050',
            'affiliation_type' => '99',
        ])
        ->assertSessionHasErrors(['document_type', 'affiliation_type']);
});

it('does not let patients set their own insurer', function () {
    $patient = Patient::factory()->withAccount()->create(['insurer_id' => null]);

    $this->actingAs($patient->user)
        ->put(route('patients.update', $patient), [
            'phone' => '3001234567',
            'document_type' => 'CC',
            'document_number' => $patient->document_number,
            'birth_date' => '1990-01-01',
            'gender' => 'female',
            'insurer_id' => Insurer::factory()->create()->id,
        ])
        ->assertSessionHasNoErrors();

    expect($patient->fresh()->insurer_id)->toBeNull();
});
