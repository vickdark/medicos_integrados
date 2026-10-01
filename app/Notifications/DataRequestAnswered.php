<?php

namespace App\Notifications;

use App\Enums\DataRequestStatus;
use App\Models\DataSubjectRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataRequestAnswered extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public DataSubjectRequest $dataRequest) {}

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
        $outcome = $this->dataRequest->status === DataRequestStatus::Resolved ? 'fue atendida' : 'fue rechazada';

        return (new MailMessage)
            ->subject('Respuesta a tu solicitud sobre tus datos personales')
            ->greeting("Hola, {$notifiable->name}")
            ->line("Tu solicitud «{$this->dataRequest->type->label()}» {$outcome}.")
            ->line("**Respuesta:** {$this->dataRequest->response}")
            ->action('Ver mis solicitudes', route('personal-data.edit'));
    }
}
