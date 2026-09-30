<?php

namespace App\Notifications;

use App\Models\Appointment;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentRescheduled extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Appointment $appointment,
        public CarbonInterface $previousDate,
        public bool $needsConfirmation = false,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->appointment->loadMissing(['patient', 'doctor.user']);

        $format = 'l j \\d\\e F \\d\\e Y, g:i A';

        $message = (new MailMessage)
            ->subject('Cita reprogramada')
            ->greeting("Hola, {$notifiable->name}")
            ->line("La cita de {$this->appointment->patient->full_name} fue reprogramada:")
            ->line('**Fecha anterior:** '.$this->previousDate->translatedFormat($format))
            ->line('**Nueva fecha:** '.$this->appointment->scheduled_at->translatedFormat($format))
            ->line("**Médico:** {$this->appointment->doctor->user->name}")
            ->line("**Motivo:** {$this->appointment->reason}");

        if ($this->needsConfirmation) {
            $message->line('La nueva fecha queda pendiente de confirmación por la clínica.');
        }

        return $message->action('Ver citas', route('appointments.index'));
    }
}
