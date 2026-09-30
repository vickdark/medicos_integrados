<?php

use App\Enums\TurnStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Turn;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lets reception hand out consecutive turns to walk-in patients', function () {
    $receptionist = User::factory()->receptionist()->create();
    $doctor = Doctor::factory()->create();
    [$first, $second] = Patient::factory()->count(2)->create();

    $this->actingAs($receptionist)
        ->post(route('turns.store'), ['patient_id' => $first->id, 'doctor_id' => $doctor->id])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', "Turno T-001 asignado a {$first->full_name}.");

    $this->actingAs($receptionist)
        ->post(route('turns.store'), ['patient_id' => $second->id, 'doctor_id' => $doctor->id]);

    $turns = Turn::query()->orderBy('number')->get();

    expect($turns->pluck('code')->all())->toBe(['T-001', 'T-002'])
        ->and($turns->first()->status)->toBe(TurnStatus::Waiting)
        ->and($turns->first()->created_by)->toBe($receptionist->id)
        ->and($turns->last()->peopleAhead())->toBe(1);
});

it('starts the numbering again every day', function () {
    Turn::factory()->create(['turn_date' => today()->subDay(), 'number' => 15]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->post(route('turns.store'), ['patient_id' => Patient::factory()->create()->id, 'doctor_id' => Doctor::factory()->create()->id]);

    expect(Turn::query()->today()->sole()->code)->toBe('T-001');
});

it('checks in a patient with an appointment today only once', function () {
    $receptionist = User::factory()->receptionist()->create();
    $appointment = Appointment::factory()->confirmed()->create(['scheduled_at' => now()->addHour()]);

    $this->actingAs($receptionist)
        ->get(route('turns.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('turns/Index')
            ->where('pendingAppointments.0.id', $appointment->id)
        );

    $this->actingAs($receptionist)
        ->post(route('turns.store'), ['appointment_id' => $appointment->id])
        ->assertSessionHasNoErrors();

    $turn = Turn::query()->sole();

    expect($turn->appointment_id)->toBe($appointment->id)
        ->and($turn->patient_id)->toBe($appointment->patient_id)
        ->and($turn->doctor_id)->toBe($appointment->doctor_id);

    $this->actingAs($receptionist)
        ->post(route('turns.store'), ['appointment_id' => $appointment->id])
        ->assertSessionHasErrors('appointment_id');

    $this->actingAs($receptionist)
        ->get(route('turns.index'))
        ->assertInertia(fn (Assert $page) => $page->has('pendingAppointments', 0));
});

it('rejects turns for appointments of another day and duplicated walk-ins', function () {
    $receptionist = User::factory()->receptionist()->create();
    $tomorrow = Appointment::factory()->confirmed()->create(['scheduled_at' => now()->addDay()]);
    $turn = Turn::factory()->create();

    $this->actingAs($receptionist)
        ->post(route('turns.store'), ['appointment_id' => $tomorrow->id])
        ->assertSessionHasErrors('appointment_id');

    $this->actingAs($receptionist)
        ->post(route('turns.store'), ['patient_id' => $turn->patient_id, 'doctor_id' => $turn->doctor_id])
        ->assertSessionHasErrors('patient_id');

    $this->actingAs($receptionist)
        ->post(route('turns.store'), [])
        ->assertSessionHasErrors(['patient_id', 'doctor_id']);
});

it('keeps turn management away from patients and doctors cannot hand out turns', function () {
    $patient = User::factory()->patient()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($patient)->get(route('turns.index'))->assertForbidden();
    $this->actingAs($doctor->user)
        ->post(route('turns.store'), ['patient_id' => Patient::factory()->create()->id, 'doctor_id' => $doctor->id])
        ->assertForbidden();
});

it('finishes the previous patient of the doctor when the next one is called', function () {
    $doctor = Doctor::factory()->create();
    $current = Turn::factory()->called()->for($doctor)->create(['number' => 1]);
    $next = Turn::factory()->for($doctor)->create(['number' => 2]);
    $otherDoctor = Turn::factory()->called()->create(['number' => 3]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('turns.status', $next), ['status' => 'called'])
        ->assertSessionHasNoErrors();

    expect($next->fresh()->status)->toBe(TurnStatus::Called)
        ->and($next->fresh()->called_at)->not->toBeNull()
        ->and($current->fresh()->status)->toBe(TurnStatus::Done)
        ->and($current->fresh()->finished_at)->not->toBeNull()
        ->and($otherDoctor->fresh()->status)->toBe(TurnStatus::Called);
});

it('only allows valid status changes', function () {
    $receptionist = User::factory()->receptionist()->create();
    $waiting = Turn::factory()->create(['number' => 1]);
    $done = Turn::factory()->done()->create(['number' => 2]);

    $this->actingAs($receptionist)
        ->patch(route('turns.status', $waiting), ['status' => 'done'])
        ->assertSessionHasErrors('status');

    $this->actingAs($receptionist)
        ->patch(route('turns.status', $done), ['status' => 'called'])
        ->assertForbidden();

    $this->actingAs($receptionist)->patch(route('turns.status', $waiting), ['status' => 'called']);
    $this->actingAs($receptionist)->patch(route('turns.status', $waiting), ['status' => 'waiting']);

    expect($waiting->fresh()->status)->toBe(TurnStatus::Waiting)
        ->and($waiting->fresh()->called_at)->toBeNull();
});

it('lets a doctor manage only their own queue', function () {
    $doctor = Doctor::factory()->create();
    $own = Turn::factory()->for($doctor)->create(['number' => 1]);
    $foreign = Turn::factory()->create(['number' => 2]);

    $this->actingAs($doctor->user)
        ->get(route('turns.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('turns', 1)
            ->where('turns.0.id', $own->id)
            ->where('can.create', false)
            ->where('patients', [])
        );

    $this->actingAs($doctor->user)
        ->patch(route('turns.status', $own), ['status' => 'called'])
        ->assertSessionHasNoErrors();

    $this->actingAs($doctor->user)
        ->patch(route('turns.status', $foreign), ['status' => 'called'])
        ->assertForbidden();
});

it('lets the doctor record the consultation of a walk-in patient from the turn', function () {
    $doctor = Doctor::factory()->create();
    $turn = Turn::factory()->called()->for($doctor)->create();

    $this->actingAs($doctor->user)
        ->get(route('turns.index'))
        ->assertInertia(fn (Assert $page) => $page->where('turns.0.can.attend', true));

    $this->actingAs($doctor->user)
        ->get(route('consultations.create', $turn->patient))
        ->assertOk();
});

it('shows the waiting room screen publicly with turn codes but no patient names', function () {
    $doctor = Doctor::factory()->create();
    $serving = Turn::factory()->called()->for($doctor)->create(['number' => 4]);
    $waiting = Turn::factory()->for($doctor)->create(['number' => 5]);
    Turn::factory()->done()->for($doctor)->create(['number' => 3]);

    $this->get(route('turns.board'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('turns/Board')
            ->where('board.serving.0.code', 'T-004')
            ->where('board.serving.0.recent', true)
            ->where('board.next.0.code', 'T-005')
            ->where('board.waiting_count', 1)
        );

    $this->getJson(route('turns.feed'))
        ->assertOk()
        ->assertJsonPath('serving.0.doctor', $doctor->user->name)
        ->assertJsonPath('next.0.code', 'T-005')
        ->assertDontSee($serving->patient->full_name)
        ->assertDontSee($waiting->patient->full_name);
});

it('shows the patient their turn and place in line on the portal', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->withAccount()->create();

    Turn::factory()->called()->for($doctor)->create(['number' => 1]);
    Turn::factory()->for($doctor)->create(['number' => 2]);
    Turn::factory()->for($doctor)->for($patient)->create(['number' => 3]);

    $this->actingAs($patient->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('myTurn.turn.code', 'T-003')
            ->where('myTurn.turn.status.value', 'waiting')
            ->where('myTurn.turn.ahead', 1)
            ->where('myTurn.turn.serving', 'T-001')
            ->where('myTurn.appointment', null)
        );
});

it('tells a patient with an appointment today to check in at reception', function () {
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->confirmed()->for($patient)->create(['scheduled_at' => now()->addHours(2)]);

    $this->actingAs($patient->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('myTurn.turn', null)
            ->where('myTurn.appointment.doctor', $appointment->doctor->user->name)
        );

    $this->actingAs(User::factory()->patient()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('myTurn', null));
});
