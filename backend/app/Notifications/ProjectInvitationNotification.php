<?php

namespace App\Notifications;

use App\Models\ProjectInvitation;
use App\Support\Roles;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Correo de invitación a un proyecto (a una dirección sin cuenta). */
class ProjectInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly ProjectInvitation $invitation, public readonly string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->invitation->project;
        $inviter = $this->invitation->inviter?->name ?? 'Alguien';

        return (new MailMessage)
            ->subject("{$inviter} te invita al proyecto {$project->name} en CodeFlow")
            ->greeting('¡Hola!')
            ->line("{$inviter} te ha invitado a unirte al proyecto «{$project->name}» como ".mb_strtolower(Roles::label($this->invitation->role)).'.')
            ->line('CodeFlow es una plataforma para guardar código y trabajar en equipo.')
            ->action('Aceptar la invitación', $this->url)
            ->line('Crea tu cuenta con este mismo correo; al verificarlo entrarás al proyecto automáticamente.')
            ->line('La invitación caduca el '.$this->invitation->expires_at->translatedFormat('j \d\e F').'. Si no esperabas este correo, ignóralo.')
            ->salutation('El equipo de CodeFlow');
    }
}
