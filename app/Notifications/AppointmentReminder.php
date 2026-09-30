<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class AppointmentReminder extends Notification implements ShouldQueue
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
        $hasPushSubscriptions = $notifiable instanceof User && $notifiable->pushSubscriptions()->exists();

        return $hasPushSubscriptions ? ['mail', WebPushChannel::class] : ['mail'];
    }

    /**
     * Get the browser push representation of the notification.
     */
    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $this->appointment->loadMissing('doctor.user');

        return (new WebPushMessage)
            ->title('Recordatorio de tu cita médica')
            ->body($this->appointment->scheduled_at->translatedFormat('l j \\d\\e F, g:i A').' · '.$this->appointment->doctor->user->name)
            ->icon('/apple-touch-icon.png')
            ->tag('appointment-reminder-'.$this->appointment->id)
            ->data(['url' => route('appointments.index', absolute: false)]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->appointment->loadMissing(['doctor.user', 'doctor.specialty', 'patient']);

        $name = $notifiable->name ?? $this->appointment->patient->full_name;

        return (new MailMessage)
            ->subject('Recordatorio: tienes una cita médica próximamente')
            ->greeting("Hola, {$name}")
            ->line('Te recordamos tu próxima cita médica:')
            ->line('**Fecha:** '.$this->appointment->scheduled_at->translatedFormat('l j \\d\\e F \\d\\e Y, g:i A'))
            ->line("**Médico:** {$this->appointment->doctor->user->name} ({$this->appointment->doctor->specialty->name})")
            ->line("**Motivo:** {$this->appointment->reason}")
            ->line('Si no puedes asistir, avísanos con anticipación para liberar el horario.');
    }
}
