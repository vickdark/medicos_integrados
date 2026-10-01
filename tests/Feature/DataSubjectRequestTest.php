<?php

use App\Enums\AuditAction;
use App\Enums\DataRequestStatus;
use App\Enums\DataRequestType;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\DataSubjectRequest;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\DataRequestAnswered;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

it('lets a patient request their data with the legal deadline for the request type', function () {
    $this->travelTo(now()->next('Monday')->setTime(9, 0));
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->post(route('personal-data.requests.store'), [
            'type' => DataRequestType::Rectification->value,
            'details' => 'Mi teléfono cambió, necesito actualizarlo.',
        ])
        ->assertSessionHasNoErrors();

    $request = DataSubjectRequest::query()->sole();

    expect($request->patient_id)->toBe($patient->id)
        ->and($request->isPending())->toBeTrue()
        ->and($request->due_at->toDateString())->toBe(now()->addWeekdays(15)->toDateString())
        ->and($request->details)->toBe('Mi teléfono cambió, necesito actualizarlo.')
        ->and(AuditLog::query()->where('action', AuditAction::Created)->where('patient_id', $patient->id)->exists())->toBeTrue();
});

it('gives 10 business days to queries and 15 to claims', function () {
    expect(DataRequestType::Access->responseBusinessDays())->toBe(10)
        ->and(DataRequestType::Rectification->responseBusinessDays())->toBe(15)
        ->and(DataRequestType::Deletion->responseBusinessDays())->toBe(15);
});

it('validates the request', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->post(route('personal-data.requests.store'), ['type' => 'otro', 'details' => 'corto'])
        ->assertSessionHasErrors(['type', 'details']);
});

it('keeps the personal data page for patients', function () {
    $this->actingAs(User::factory()->doctor()->create())
        ->get(route('personal-data.edit'))
        ->assertNotFound();

    $this->actingAs(User::factory()->receptionist()->create())
        ->post(route('personal-data.requests.store'), ['type' => 'access', 'details' => 'Quiero ver mis datos'])
        ->assertForbidden();
});

it('shows the patient only their own requests', function () {
    $patient = Patient::factory()->withAccount()->create();
    DataSubjectRequest::factory()->for($patient)->create(['details' => 'Mi solicitud']);
    DataSubjectRequest::factory()->create(['details' => 'De otro paciente']);

    $this->actingAs($patient->user)
        ->get(route('personal-data.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/PersonalData')
            ->has('requests', 1)
            ->where('requests.0.details', 'Mi solicitud'));
});

it('encrypts the details and the answer in the database', function () {
    $request = DataSubjectRequest::factory()->create(['details' => 'detalle-secreto', 'response' => 'respuesta-secreta']);

    $raw = DB::table('data_subject_requests')->where('id', $request->id)->first();

    expect($raw->details)->not->toContain('detalle-secreto')
        ->and($raw->response)->not->toContain('respuesta-secreta');
});

it('lets the patient download a copy of their data without internal notes', function () {
    $patient = Patient::factory()->withAccount()->create(['allergies' => 'Penicilina']);
    Consultation::factory()->for($patient)->create(['diagnosis' => 'Gripe', 'notes' => 'nota-interna-del-medico']);

    $response = $this->actingAs($patient->user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('personal-data.download'))
        ->assertOk()
        ->assertDownload();

    $content = $response->streamedContent();

    expect($content)->toContain('Penicilina')
        ->toContain('Gripe')
        ->not->toContain('nota-interna-del-medico')
        ->and(AuditLog::query()->where('action', AuditAction::Exported)->where('patient_id', $patient->id)->exists())->toBeTrue();
});

it('asks for the password before downloading the data', function () {
    $patient = Patient::factory()->withAccount()->create();

    $this->actingAs($patient->user)
        ->get(route('personal-data.download'))
        ->assertRedirect(route('password.confirm'));
});

it('lets only the administrator review and answer the requests', function () {
    Notification::fake();
    $request = DataSubjectRequest::factory()->for(Patient::factory()->withAccount())->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('data-requests.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('data-requests.answer', $request), ['status' => 'resolved', 'response' => 'Ya está listo.'])
        ->assertForbidden();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('data-requests.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('data-requests/Index')
            ->has('requests.data', 1)
            ->where('pendingCount', 1));

    $this->actingAs($admin)
        ->patch(route('data-requests.answer', $request), ['status' => 'resolved', 'response' => 'Actualizamos tu teléfono.'])
        ->assertSessionHasNoErrors();

    $request->refresh();

    expect($request->status)->toBe(DataRequestStatus::Resolved)
        ->and($request->response)->toBe('Actualizamos tu teléfono.')
        ->and($request->responded_by)->toBe($admin->id)
        ->and($request->responded_at)->not->toBeNull();

    Notification::assertSentTo($request->patient->user, DataRequestAnswered::class);
});

it('does not let a request be answered twice', function () {
    $request = DataSubjectRequest::factory()->create(['status' => DataRequestStatus::Resolved]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('data-requests.answer', $request), ['status' => 'rejected', 'response' => 'Otra respuesta distinta.'])
        ->assertForbidden();
});

it('flags the pending requests past their deadline', function () {
    $overdue = DataSubjectRequest::factory()->overdue()->create();
    $onTime = DataSubjectRequest::factory()->create();
    $answered = DataSubjectRequest::factory()->overdue()->create(['status' => DataRequestStatus::Resolved]);

    expect($overdue->isOverdue())->toBeTrue()
        ->and($onTime->isOverdue())->toBeFalse()
        ->and($answered->isOverdue())->toBeFalse();
});

it('asks the patient to accept the policy again when its version changes', function () {
    $user = User::factory()->withOutdatedPrivacyPolicy()->create();
    Patient::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('privacy.accept'));

    $this->actingAs($user)
        ->get(route('privacy.accept'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('PrivacyAccept')
            ->where('version', config('privacy.version'))
            ->where('previousVersion', '0.1'));

    $this->actingAs($user)
        ->post(route('privacy.accept.store'))
        ->assertSessionHasErrors('privacy');

    $this->actingAs($user)
        ->post(route('privacy.accept.store'), ['privacy' => 'on'])
        ->assertRedirect();

    $user->refresh();

    expect($user->privacy_policy_version)->toBe(config('privacy.version'))
        ->and($user->privacy_accepted_at)->not->toBeNull();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('does not ask the clinic staff to accept the patients policy', function () {
    $doctor = User::factory()->doctor()->create(['privacy_policy_version' => null]);

    expect($doctor->mustAcceptPrivacyPolicy())->toBeFalse();
    $this->actingAs(User::factory()->admin()->create(['privacy_policy_version' => null]))
        ->get(route('dashboard'))
        ->assertOk();
});

it('lets a patient who must accept the policy read it and log out', function () {
    $user = User::factory()->withOutdatedPrivacyPolicy()->create();

    $this->actingAs($user)->get(route('privacy'))->assertOk();
    $this->actingAs($user)->post(route('logout'))->assertRedirect();
});
