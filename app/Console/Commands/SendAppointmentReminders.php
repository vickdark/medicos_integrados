<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendAppointmentReminders extends Command
{
    /**
     * Hours before the appointment from which the reminder goes out.
     */
    public const HOURS_AHEAD = 24;

    /**
     * Appointments closer than this are not reminded, it would be too late to be useful.
     */
    public const MIN_HOURS_AHEAD = 2;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía por correo el recordatorio a los pacientes con citas confirmadas en las próximas 24 horas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sent = 0;

        Appointment::query()
            ->where('status', AppointmentStatus::Confirmed)
            ->whereNull('reminder_sent_at')
            ->whereBetween('scheduled_at', [now()->addHours(self::MIN_HOURS_AHEAD), now()->addHours(self::HOURS_AHEAD)])
            ->with(['patient.user', 'doctor.user', 'doctor.specialty'])
            ->lazyById(200)
            ->each(function (Appointment $appointment) use (&$sent): void {
                if ($this->remind($appointment)) {
                    $appointment->update(['reminder_sent_at' => now()]);
                    $sent++;
                }
            });

        $this->info("Recordatorios enviados: {$sent}.");

        return self::SUCCESS;
    }

    /**
     * Notify the patient through their account, or through the email on their record.
     */
    private function remind(Appointment $appointment): bool
    {
        $patient = $appointment->patient;
        $notification = new AppointmentReminder($appointment);

        if ($patient->user) {
            $patient->user->notify($notification);

            return true;
        }

        if (filled($patient->email)) {
            Notification::route('mail', $patient->email)->notify($notification);

            return true;
        }

        return false;
    }
}
