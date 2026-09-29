<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\ConsultationAttachment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

it('deletes the attachment and its stored file', function () {
    $attachment = ConsultationAttachment::factory()->create();
    Storage::disk(ConsultationAttachment::DISK)->put($attachment->path, 'contenido');

    $this->actingAs($attachment->consultation->doctor->user)
        ->delete(route('attachments.destroy', $attachment))
        ->assertRedirect();

    expect(ConsultationAttachment::query()->count())->toBe(0);
    Storage::disk(ConsultationAttachment::DISK)->assertMissing($attachment->path);
});
