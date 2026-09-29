<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Appointment $appointment) {}

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
        $this->appointment->loadMissing(['doctor.user', 'doctor.specialty']);

        return (new MailMessage)
            ->subject('Tu cita ha sido confirmada')
            ->greeting("Hola, {$notifiable->name}")
            ->line('Tu cita médica ha sido confirmada:')
            ->line('**Fecha:** '.$this->appointment->scheduled_at->translatedFormat('l j \\d\\e F \\d\\e Y, H:i'))
            ->line("**Médico:** {$this->appointment->doctor->user->name} ({$this->appointment->doctor->specialty->name})")
            ->line("**Motivo:** {$this->appointment->reason}")
            ->action('Ver mis citas', route('appointments.index'))
            ->line('Si no puedes asistir, cancela la cita desde el portal con anticipación.');
    }
}
