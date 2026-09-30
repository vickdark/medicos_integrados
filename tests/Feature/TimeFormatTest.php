<?php

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentConfirmed;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('summarizes office hours in a 12-hour clock', function () {
    $schedule = DoctorSchedule::factory()->create([
        'day_of_week' => Carbon::MONDAY,
        'starts_at' => '08:00',
        'ends_at' => '14:30',
    ]);

    expect($schedule->summary())->toBe('Lunes 8:00 AM – 2:30 PM');
});

it('keeps validating office hours with 24-hour values', function () {
    $schedule = DoctorSchedule::factory()->create(['starts_at' => '13:00', 'ends_at' => '18:00']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('schedules.store', $schedule->doctor), [
            'day_of_week' => $schedule->day_of_week,
            'starts_at' => '5:00 PM',
            'ends_at' => '19:00',
        ])
        ->assertSessionHasErrors('starts_at');
});

it('shows the appointment time in a 12-hour clock in the confirmation email', function () {
    $patient = Patient::factory()->withAccount()->create();
    $appointment = Appointment::factory()->for($patient)->confirmed()->create([
        'scheduled_at' => now()->addDays(3)->setTime(15, 30),
    ]);

    $line = collect((new AppointmentConfirmed($appointment))->toMail($patient->user)->introLines)
        ->first(fn ($line) => str_contains((string) $line, 'Fecha'));

    expect($line)->toMatch('/3:30\s?(PM|p\. ?m\.)/i')->not->toContain('15:30');
});

it('exports appointment times in a 12-hour clock', function () {
    Appointment::factory()->create(['scheduled_at' => now()->addDays(2)->setTime(9, 15)]);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('appointments.export', ['format' => 'xlsx']));

    $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
    file_put_contents($path, $response->streamedContent());
    $sheet = IOFactory::load($path)->getActiveSheet();
    unlink($path);

    expect($sheet->getCell('B2')->getValue())->toBe('9:15 AM');
});
