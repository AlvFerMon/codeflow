<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Aviso en la bandeja de notificaciones de un usuario por un cambio en uno de sus proyectos.
 * Se guarda en la tabla "notifications" (canal database), de forma síncrona.
 */
class ProjectActivityNotification extends Notification
{
    use Queueable;

    public const KIND_CHANGE = 'change';

    public const KIND_ALERT = 'alert';

    /**
     * @param  array{kind:string,event:string,title:string,message:string,project_id:int,project_name:string,repository_id:?int,subject_type:?string,subject_id:?int,url:?string,actor:?array{id:int,name:string}}  $payload
     */
    public function __construct(public readonly array $payload) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
