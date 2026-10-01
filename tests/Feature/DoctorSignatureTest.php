<?php

use App\Mail\ClinicalDocumentMail;
use App\Models\ClinicalDocument;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
});

it('lets the administrator register and remove the signature of a doctor', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();
    $payload = fn (array $extra = []) => [
        'role' => 'doctor',
        'name' => $doctor->user->name,
        'email' => $doctor->user->email,
        'specialty_id' => $doctor->specialty_id,
        'license_number' => $doctor->license_number,
        'consultation_fee' => '50',
        'slot_minutes' => 30,
        ...$extra,
    ];

    $this->actingAs($admin)
        ->put(route('users.update', $doctor->user), $payload(['signature' => UploadedFile::fake()->image('firma.jpg', 1200, 400)]))
        ->assertSessionHasNoErrors();

    $path = $doctor->fresh()->signature_path;

    expect($path)->toEndWith('.png')
        ->and($doctor->fresh()->hasSignature())->toBeTrue();
    Storage::disk('local')->assertExists($path);
    [$width] = getimagesizefromstring(Storage::disk('local')->get($path));
    expect($width)->toBeLessThanOrEqual(600);

    $this->actingAs($admin)->put(route('users.update', $doctor->user), $payload(['remove_signature' => '1']));

    expect($doctor->fresh()->signature_path)->toBeNull();
    Storage::disk('local')->assertMissing($path);
});

it('lets doctors upload and remove their own signature', function () {
    $doctor = Doctor::factory()->create();

    $this->actingAs($doctor->user)
        ->post(route('doctor-profile.signature.update'), ['signature' => UploadedFile::fake()->image('firma.png', 500, 200)])
        ->assertSessionHasNoErrors();

    expect($doctor->fresh()->hasSignature())->toBeTrue();

    $this->actingAs($doctor->user)
        ->get(route('doctor-profile.show'))
        ->assertInertia(fn (Assert $page) => $page->whereNot('profile.signature_url', null));

    $this->actingAs($doctor->user)->delete(route('doctor-profile.signature.destroy'));

    expect($doctor->fresh()->hasSignature())->toBeFalse();

    $this->actingAs($doctor->user)
        ->post(route('doctor-profile.signature.update'), ['signature' => UploadedFile::fake()->create('firma.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('signature');

    $this->actingAs(User::factory()->receptionist()->create())
        ->post(route('doctor-profile.signature.update'), ['signature' => UploadedFile::fake()->image('firma.png')])
        ->assertForbidden();
});

it('shows the signature image only to its doctor and the administrator', function () {
    $doctor = Doctor::factory()->create();
    $this->actingAs($doctor->user)->post(route('doctor-profile.signature.update'), ['signature' => UploadedFile::fake()->image('firma.png')]);

    $this->actingAs($doctor->user)->get(route('doctors.signature', $doctor))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get(route('doctors.signature', $doctor))->assertOk();
    $this->actingAs(Doctor::factory()->create()->user)->get(route('doctors.signature', $doctor))->assertForbidden();
    $this->actingAs(User::factory()->patient()->create())->get(route('doctors.signature', $doctor))->assertForbidden();
});

it('prints the signature on the documents or marks them as not valid without it', function () {
    $unsigned = Consultation::factory()->create();
    $document = ClinicalDocument::factory()->for($unsigned)->create();

    $html = view('pdf.clinical-document', ['document' => $document, 'consultation' => $unsigned, 'isOfficial' => true, 'generatedAt' => now()])->render();

    expect($html)->toContain('Documento no válido por falta de firma del médico')
        ->toContain('SIN FIRMA')
        ->not->toContain('data:image');

    $signedDoctor = Doctor::factory()->create();
    $this->actingAs($signedDoctor->user)->post(route('doctor-profile.signature.update'), ['signature' => UploadedFile::fake()->image('firma.png')]);
    $signed = Consultation::factory()->for($signedDoctor->fresh())->create();

    $html = view('pdf.prescription', ['consultation' => $signed->load('prescriptions'), 'isOfficial' => true, 'generatedAt' => now()])->render();

    expect($html)->toContain('data:image/png;base64')
        ->toContain('Registro médico')
        ->not->toContain('falta de firma')
        ->not->toContain('Colegiatura');
});

it('tells on the consultation page whether the doctor has signed', function () {
    $consultation = Consultation::factory()->create();

    $this->actingAs($consultation->doctor->user)
        ->get(route('consultations.show', $consultation))
        ->assertInertia(fn (Assert $page) => $page->where('doctorHasSignature', false));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('doctors.index'))
        ->assertInertia(fn (Assert $page) => $page->where('doctors.data.0.has_signature', false));
});

it('emails a signed clinical document to the patient', function () {
    Mail::fake();
    $consultation = Consultation::factory()->for(Doctor::factory()->signed())->create();
    $document = ClinicalDocument::factory()->sickLeave()->for($consultation)->create();

    $this->actingAs($consultation->doctor->user)
        ->post(route('clinical-documents.email', $document), ['email' => 'paciente@example.com'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    Mail::assertSent(ClinicalDocumentMail::class, fn (ClinicalDocumentMail $mail) => $mail->hasTo('paciente@example.com')
        && $mail->document->is($document));
});

it('builds the clinical document email with the PDF attached', function () {
    $document = ClinicalDocument::factory()->create();
    $mail = new ClinicalDocumentMail($document);

    expect($mail->attachments())->toHaveCount(1);
    $mail->assertSeeInHtml($document->number);
});

it('does not email unsigned documents nor lets other users send them', function () {
    Mail::fake();
    $consultation = Consultation::factory()->create();
    $document = ClinicalDocument::factory()->for($consultation)->create();

    $this->actingAs($consultation->doctor->user)
        ->post(route('clinical-documents.email', $document), ['email' => 'paciente@example.com'])
        ->assertSessionHasErrors('email');

    $this->actingAs(Doctor::factory()->signed()->create()->user)
        ->post(route('clinical-documents.email', $document), ['email' => 'paciente@example.com'])
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('clinical-documents.email', $document), ['email' => 'paciente@example.com'])
        ->assertForbidden();

    Mail::assertNothingSent();
});
