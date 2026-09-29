<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Seed demo users for every role plus sample clinical data.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Administrador',
            'email' => 'admin@medicos.test',
        ]);

        User::factory()->receptionist()->create([
            'name' => 'Recepción',
            'email' => 'recepcion@medicos.test',
        ]);

        $specialties = Specialty::query()->get();

        $mainDoctor = Doctor::factory()
            ->for(User::factory()->doctor()->state([
                'name' => 'Dra. Laura Méndez',
                'email' => 'medico@medicos.test',
            ]))
            ->for($specialties->firstWhere('name', 'Medicina General'))
            ->create();

        $doctors = Doctor::factory()
            ->count(4)
            ->sequence(fn ($sequence) => ['specialty_id' => $specialties->random()->id])
            ->create()
            ->prepend($mainDoctor);

        $doctors->each(function (Doctor $doctor): void {
            foreach (range(1, 5) as $weekday) {
                $doctor->schedules()->createMany([
                    ['day_of_week' => $weekday, 'starts_at' => '08:00', 'ends_at' => '13:00'],
                    ['day_of_week' => $weekday, 'starts_at' => '14:00', 'ends_at' => '18:00'],
                ]);
            }
        });

        $demoPatient = Patient::factory()
            ->for(User::factory()->state([
                'name' => 'Carlos Pérez',
                'email' => 'paciente@medicos.test',
            ]))
            ->create([
                'first_name' => 'Carlos',
                'last_name' => 'Pérez',
                'email' => 'paciente@medicos.test',
                'allergies' => 'Penicilina',
            ]);

        $patients = Patient::factory()->count(25)->create()->prepend($demoPatient);

        $patients->each(function (Patient $patient) use ($doctors, $mainDoctor, $demoPatient, $admin): void {
            $doctor = $patient->is($demoPatient) ? $mainDoctor : $doctors->random();

            $completedAppointments = Appointment::factory()
                ->count(fake()->numberBetween(1, 3))
                ->completed()
                ->for($patient)
                ->for($doctor)
                ->create();

            $completedAppointments->each(function (Appointment $appointment) use ($admin): void {
                $consultation = Consultation::factory()
                    ->for($appointment->patient)
                    ->for($appointment->doctor)
                    ->create([
                        'appointment_id' => $appointment->id,
                        'consulted_at' => $appointment->scheduled_at,
                        'reason' => $appointment->reason,
                    ]);

                Prescription::factory()->count(fake()->numberBetween(0, 2))->for($consultation)->create();

                Payment::factory()->for($appointment->patient)->create([
                    'appointment_id' => $appointment->id,
                    'amount' => $appointment->doctor->consultation_fee,
                    'concept' => 'Consulta médica',
                    'paid_at' => $appointment->scheduled_at->format('Y-m-d'),
                    'recorded_by' => $admin->id,
                ]);
            });

            Appointment::factory()
                ->for($patient)
                ->for($doctor)
                ->state(fn () => ['status' => fake()->randomElement(['requested', 'confirmed'])])
                ->create();
        });

        Payment::factory()->pending()->for($demoPatient)->create([
            'concept' => 'Examen de laboratorio',
            'amount' => 45,
            'recorded_by' => $admin->id,
        ]);
    }
}
