<?php

use App\Enums\AttachmentStatus;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\ConsultationAttachment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake(ConsultationAttachment::DISK);
});

it('lets the treating doctor attach files to the consultation', function () {
    $consultation = Consultation::factory()->create();

    $this->actingAs($consultation->doctor->user)
        ->post(route('attachments.store', $consultation), [
            'file' => UploadedFile::fake()->create('hemograma.pdf', 200, 'application/pdf'),
            'description' => 'Hemograma completo',
        ])
        ->assertSessionHasNoErrors();

    $attachment = ConsultationAttachment::query()->sole();

    expect($attachment->original_name)->toBe('hemograma.pdf')
        ->and($attachment->uploaded_by)->toBe($consultation->doctor->user_id);

    Storage::disk(ConsultationAttachment::DISK)->assertExists($attachment->path);

    expect($attachment->is_encrypted)->toBeTrue()
        ->and($attachment->path)->toEndWith('.enc');

    expect(AuditLog::query()->where('action', AuditAction::Uploaded)->where('patient_id', $consultation->patient_id)->exists())->toBeTrue();
});

it('rejects files that are not PDFs or images', function () {
    $consultation = Consultation::factory()->create();

    $this->actingAs($consultation->doctor->user)
        ->post(route('attachments.store', $consultation), [
            'file' => UploadedFile::fake()->create('script.exe', 10),
        ])
        ->assertSessionHasErrors('file');
});

it('forbids uploads from anyone other than the doctor of the consultation', function () {
    $consultation = Consultation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('attachments.store', $consultation), [
            'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        ])
        ->assertForbidden();
});

it('lets the patient download their own attachments only', function () {
    $patient = Patient::factory()->withAccount()->create();
    $attachment = ConsultationAttachment::factory()
        ->for(Consultation::factory()->for($patient))
        ->create(['original_name' => 'resultado.pdf']);
    Storage::disk(ConsultationAttachment::DISK)->put($attachment->path, 'contenido');

    $this->actingAs($patient->user)
        ->get(route('attachments.show', $attachment))
        ->assertOk()
        ->assertDownload('resultado.pdf');

    $this->actingAs(Patient::factory()->withAccount()->create()->user)
        ->get(route('attachments.show', $attachment))
        ->assertForbidden();

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('attachments.show', $attachment))
        ->assertForbidden();
});

it('voids the attachment with an internal reason and keeps the stored file', function () {
    $attachment = ConsultationAttachment::factory()->create();
    Storage::disk(ConsultationAttachment::DISK)->put($attachment->path, 'contenido');
    $doctor = $attachment->consultation->doctor->user;

    $this->actingAs($doctor)
        ->post(route('attachments.status', $attachment), ['status' => 'voided', 'reason' => 'Archivo de otro paciente subido por error'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $attachment->refresh();

    expect($attachment->status)->toBe(AttachmentStatus::Voided)
        ->and($attachment->status_changed_by)->toBe($doctor->id)
        ->and($attachment->status_changed_by_name)->toBe($doctor->name)
        ->and($attachment->status_reason)->toBe('Archivo de otro paciente subido por error')
        ->and($attachment->replaced_by_id)->toBeNull()
        ->and(AuditLog::query()->where('description', 'like', 'Anuló el archivo%')->exists())->toBeTrue();
    Storage::disk(ConsultationAttachment::DISK)->assertExists($attachment->path);

    $this->actingAs($doctor)
        ->get(route('consultations.show', $attachment->consultation))
        ->assertInertia(fn (Assert $page) => $page
            ->where('consultation.attachments.0.status.value', 'voided')
            ->where('consultation.attachments.0.history.reason', 'Archivo de otro paciente subido por error')
            ->where('consultation.attachments.0.history.changed_by', $doctor->name)
        );

    $this->actingAs($doctor)->get(route('attachments.show', $attachment))->assertOk();
});

it('corrects the attachment with a new file keeping the original and its internal note', function () {
    $patient = Patient::factory()->withAccount()->create();
    $consultation = Consultation::factory()->for($patient)->create();
    $original = ConsultationAttachment::factory()->for($consultation)->create(['description' => 'Hemograma']);
    Storage::disk(ConsultationAttachment::DISK)->put($original->path, 'contenido');
    $doctor = $consultation->doctor->user;

    $this->actingAs($doctor)
        ->post(route('attachments.status', $original), [
            'status' => 'corrected',
            'reason' => 'Faltaba la página de conclusiones',
            'file' => UploadedFile::fake()->create('hemograma-completo.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $original->refresh();
    $replacement = $original->replacedBy;

    expect($original->status)->toBe(AttachmentStatus::Corrected)
        ->and($original->status_reason)->toBe('Faltaba la página de conclusiones')
        ->and($replacement->original_name)->toBe('hemograma-completo.pdf')
        ->and($replacement->description)->toBe('Hemograma')
        ->and($replacement->replaces_id)->toBe($original->id)
        ->and($replacement->isActive())->toBeTrue()
        ->and(AuditLog::query()->where('description', 'like', 'Corrigió el archivo%')->exists())->toBeTrue();
    Storage::disk(ConsultationAttachment::DISK)->assertExists($original->path);
    Storage::disk(ConsultationAttachment::DISK)->assertExists($replacement->path);

    $this->actingAs($doctor)
        ->get(route('consultations.show', $consultation))
        ->assertInertia(fn (Assert $page) => $page
            ->has('consultation.attachments', 2)
            ->where('consultation.attachments.0.history.replaced_by.name', 'hemograma-completo.pdf')
            ->where('consultation.attachments.1.history.replaces.name', $original->original_name)
        );

    $this->actingAs($patient->user)
        ->get(route('consultations.show', $consultation))
        ->assertDontSee('Faltaba la página de conclusiones')
        ->assertInertia(fn (Assert $page) => $page
            ->has('consultation.attachments', 1)
            ->where('consultation.attachments.0.id', $replacement->id)
            ->missing('consultation.attachments.0.history')
        );

    $this->actingAs($patient->user)->get(route('attachments.show', $original))->assertNotFound();
    $this->actingAs($patient->user)->get(route('attachments.show', $replacement))->assertOk();
});

it('requires the new file for a correction and a reason in both cases', function () {
    $attachment = ConsultationAttachment::factory()->create();
    $doctor = $attachment->consultation->doctor->user;

    $this->actingAs($doctor)
        ->post(route('attachments.status', $attachment), ['status' => 'corrected', 'reason' => 'Motivo válido'])
        ->assertSessionHasErrors('file');

    $this->actingAs($doctor)
        ->post(route('attachments.status', $attachment), ['status' => 'voided', 'reason' => ''])
        ->assertSessionHasErrors('reason');

    $this->actingAs($doctor)
        ->post(route('attachments.status', $attachment), ['status' => 'active', 'reason' => 'Motivo válido'])
        ->assertSessionHasErrors('status');

    expect($attachment->fresh()->isActive())->toBeTrue()
        ->and(ConsultationAttachment::query()->count())->toBe(1);
});

it('closes an attachment only once and only by the treating doctor', function () {
    $attachment = ConsultationAttachment::factory()->create();
    $doctor = $attachment->consultation->doctor->user;

    foreach ([User::factory()->admin()->create(), User::factory()->receptionist()->create()] as $user) {
        $this->actingAs($user)
            ->post(route('attachments.status', $attachment), ['status' => 'voided', 'reason' => 'Motivo cualquiera'])
            ->assertForbidden();
    }

    $this->actingAs($doctor)->post(route('attachments.status', $attachment), ['status' => 'voided', 'reason' => 'Primera anulación']);
    $this->actingAs($doctor)
        ->post(route('attachments.status', $attachment), ['status' => 'voided', 'reason' => 'Segunda anulación'])
        ->assertForbidden();

    expect($attachment->fresh()->status_reason)->toBe('Primera anulación');
});

it('hides voided attachments from the patient', function () {
    $patient = Patient::factory()->withAccount()->create();
    $consultation = Consultation::factory()->for($patient)->create();
    $kept = ConsultationAttachment::factory()->for($consultation)->create();
    $voided = ConsultationAttachment::factory()->for($consultation)->create();
    $voided->void($consultation->doctor->user, 'Subido por error');

    $this->actingAs($patient->user)
        ->get(route('consultations.show', $consultation))
        ->assertInertia(fn (Assert $page) => $page
            ->has('consultation.attachments', 1)
            ->where('consultation.attachments.0.id', $kept->id)
        );

    $this->actingAs($patient->user)->get(route('attachments.show', $voided))->assertNotFound();
});

it('never deletes an attachment nor changes it after storing it', function () {
    $attachment = ConsultationAttachment::factory()->create();

    expect(fn () => $attachment->delete())->toThrow(LogicException::class)
        ->and(fn () => $attachment->update(['original_name' => 'otro.pdf']))->toThrow(LogicException::class);

    $doctor = $attachment->consultation->doctor->user;
    $attachment->fresh()->void($doctor, 'Motivo válido');

    expect(fn () => $attachment->fresh()->void($doctor, 'Otra vez'))->toThrow(LogicException::class)
        ->and($attachment->fresh()->status_reason)->toBe('Motivo válido')
        ->and(ConsultationAttachment::query()->count())->toBe(1);
});

it('stores the file encrypted and downloads it decrypted', function () {
    $consultation = Consultation::factory()->create();
    $file = UploadedFile::fake()->createWithContent('resultado.pdf', 'contenido-clinico-secreto');

    $this->actingAs($consultation->doctor->user)
        ->post(route('attachments.store', $consultation), ['file' => $file])
        ->assertSessionHasNoErrors();

    $attachment = ConsultationAttachment::query()->sole();

    expect(Storage::disk(ConsultationAttachment::DISK)->get($attachment->path))->not->toContain('contenido-clinico-secreto');

    $this->actingAs($consultation->doctor->user)
        ->get(route('attachments.show', $attachment))
        ->assertOk()
        ->assertDownload('resultado.pdf');

    expect($attachment->contents())->toBe('contenido-clinico-secreto');
});

it('encrypts the attachments stored before the encryption existed', function () {
    $attachment = ConsultationAttachment::factory()->create();
    Storage::disk(ConsultationAttachment::DISK)->put($attachment->path, 'contenido-antiguo');

    expect($attachment->contents())->toBe('contenido-antiguo');

    $this->artisan('attachments:encrypt')->assertSuccessful();

    $attachment->refresh();

    expect($attachment->is_encrypted)->toBeTrue()
        ->and(Storage::disk(ConsultationAttachment::DISK)->get($attachment->path))->not->toContain('contenido-antiguo')
        ->and($attachment->contents())->toBe('contenido-antiguo');
});
