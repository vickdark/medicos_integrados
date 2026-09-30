<?php

use App\Enums\ConsultationSection;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\ConsultationAddendum;
use App\Models\Doctor;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function addendumPayload(array $overrides = []): array
{
    return [
        'section' => 'prescription',
        'reason' => 'Error de transcripción en la dosis',
        'content' => 'La dosis correcta de amoxicilina es 500 mg cada 8 horas.',
        ...$overrides,
    ];
}

it('lets the doctor who recorded the consultation add a clarifying note without changing the original', function () {
    $consultation = Consultation::factory()->create(['diagnosis' => 'Faringitis aguda']);
    $doctor = $consultation->doctor->user;

    $this->actingAs($doctor)
        ->post(route('consultations.addenda.store', $consultation), addendumPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $addendum = ConsultationAddendum::query()->sole();

    expect($addendum->section)->toBe(ConsultationSection::Prescription)
        ->and($addendum->user_id)->toBe($doctor->id)
        ->and($addendum->author_name)->toBe($doctor->name)
        ->and($addendum->content)->toBe('La dosis correcta de amoxicilina es 500 mg cada 8 horas.')
        ->and($consultation->fresh()->diagnosis)->toBe('Faringitis aguda')
        ->and(AuditLog::query()->where('description', 'like', 'Agregó una nota aclaratoria%')->exists())->toBeTrue();

    $this->actingAs($doctor)
        ->get(route('consultations.show', $consultation))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.add_addendum', true)
            ->has('consultation.addenda', 1)
            ->where('consultation.addenda.0.section.label', 'Receta')
            ->where('consultation.addenda.0.reason', 'Error de transcripción en la dosis')
            ->where('consultation.addenda.0.author', $doctor->name)
            ->where('consultation.diagnosis', 'Faringitis aguda')
        );
});

it('shows the clarifying notes to the patient but only the author can add them', function () {
    $consultation = Consultation::factory()->create();
    ConsultationAddendum::factory()->for($consultation)->create(['content' => 'Aclaración visible']);
    $patientUser = User::factory()->patient()->create();
    $consultation->patient->update(['user_id' => $patientUser->id]);

    $this->actingAs($patientUser)
        ->get(route('consultations.show', $consultation))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.add_addendum', false)
            ->where('consultation.addenda.0.content', 'Aclaración visible')
        );

    foreach ([$patientUser, User::factory()->admin()->create(), User::factory()->receptionist()->create(), Doctor::factory()->create()->user] as $user) {
        $this->actingAs($user)
            ->post(route('consultations.addenda.store', $consultation), addendumPayload())
            ->assertForbidden();
    }

    expect(ConsultationAddendum::query()->count())->toBe(1);
});

it('requires the section, reason and content of the note', function () {
    $consultation = Consultation::factory()->create();

    $this->actingAs($consultation->doctor->user)
        ->post(route('consultations.addenda.store', $consultation), ['section' => 'nope', 'reason' => 'x', 'content' => ''])
        ->assertSessionHasErrors(['section', 'reason', 'content']);

    expect(ConsultationAddendum::query()->count())->toBe(0);
});

it('never lets a saved consultation or note be changed or removed', function () {
    $consultation = Consultation::factory()->create(['diagnosis' => 'Original']);
    $addendum = ConsultationAddendum::factory()->for($consultation)->create();

    expect(fn () => $consultation->update(['diagnosis' => 'Cambiado']))->toThrow(LogicException::class)
        ->and(fn () => $consultation->delete())->toThrow(LogicException::class)
        ->and(fn () => $addendum->update(['content' => 'Cambiado']))->toThrow(LogicException::class)
        ->and(fn () => $addendum->delete())->toThrow(LogicException::class);

    expect($consultation->fresh()->diagnosis)->toBe('Original')
        ->and(ConsultationAddendum::query()->count())->toBe(1);
});

it('includes the clarifying notes in the clinical history PDF', function () {
    $consultation = Consultation::factory()->create();
    ConsultationAddendum::factory()->for($consultation)->create();

    $this->actingAs($consultation->doctor->user)
        ->get(route('patients.history', $consultation->patient))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('counts the notes of each consultation on the patient record', function () {
    $consultation = Consultation::factory()->create();
    ConsultationAddendum::factory()->count(2)->for($consultation)->create();

    $this->actingAs($consultation->doctor->user)
        ->get(route('patients.show', $consultation->patient))
        ->assertInertia(fn (Assert $page) => $page->where('consultations.0.addenda_count', 2));
});
