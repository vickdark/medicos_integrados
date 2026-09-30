<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends the access details of an account to its owner.
 *
 * It is deliberately not queued: a queued notification would keep the temporary
 * password in clear text in the jobs table. Send it with notifyNow().
 */
class AccountCredentials extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public string $temporaryPassword, public bool $isNewAccount = true) {}

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
     *
     * @param  User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        return (new MailMessage)
            ->subject($this->isNewAccount ? "Tus datos de acceso a {$appName}" : "Tu contraseña de {$appName} fue actualizada")
            ->greeting("Hola, {$notifiable->name}")
            ->line($this->isNewAccount
                ? "Se creó una cuenta para ti en {$appName} con el rol de **{$notifiable->role->label()}**."
                : 'Un administrador actualizó la contraseña de tu cuenta.')
            ->line("**Correo:** {$notifiable->email}")
            ->line("**Contraseña temporal:** {$this->temporaryPassword}")
            ->action('Iniciar sesión', route('login'))
            ->line('Es una contraseña temporal: al iniciar sesión el sistema te pedirá cambiarla por una propia.')
            ->line('No compartas este correo. Si no esperabas esta cuenta, avisa a la clínica.');
    }
}
