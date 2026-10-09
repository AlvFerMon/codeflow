<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Correo con el código de 6 dígitos para verificar el email. */
class VerificationCode extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code, public readonly int $minutes) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu código de verificación de CodeFlow: '.$this->code)
            ->greeting('¡Hola, '.$notifiable->name.'!')
            ->line('Este es tu código para verificar tu email en CodeFlow:')
            ->line(new \Illuminate\Support\HtmlString('<p style="font-size:32px;font-weight:700;letter-spacing:8px;text-align:center;margin:24px 0;font-family:monospace;">'.e($this->code).'</p>'))
            ->line('Escríbelo en la pantalla de verificación. Caduca en '.$this->minutes.' minutos.')
            ->line('Si no has creado ninguna cuenta en CodeFlow, ignora este mensaje.')
            ->salutation('El equipo de CodeFlow');
    }
}
