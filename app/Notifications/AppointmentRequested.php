<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentRequested extends Notification implements ShouldQueue
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
        $this->appointment->loadMissing(['patient', 'doctor.user']);

        return (new MailMessage)
            ->subject('Nueva solicitud de cita')
            ->greeting("Hola, {$notifiable->name}")
            ->line("{$this->appointment->patient->full_name} solicitó una cita:")
            ->line('**Fecha:** '.$this->appointment->scheduled_at->translatedFormat('l j \\d\\e F \\d\\e Y, H:i'))
            ->line("**Médico:** {$this->appointment->doctor->user->name}")
            ->line("**Motivo:** {$this->appointment->reason}")
            ->action('Revisar solicitudes', route('appointments.index', ['status' => 'requested']));
    }
}
