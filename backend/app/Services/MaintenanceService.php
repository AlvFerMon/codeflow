<?php

namespace App\Services;

use App\Models\ProjectInvitation;
use Illuminate\Support\Facades\DB;

/**
 * Tareas diarias de mantenimiento. Se lanzan con `php artisan codeflow:daily` (servidor propio, con el
 * programador de Laravel) o desde Vercel Cron llamando a /api/cron/daily (ver vercel.json).
 */
class MaintenanceService
{
    public const TOKEN_IDLE_DAYS = 180;

    public function __construct(private TrashService $trash) {}

    /** @return array<string,int> qué se ha hecho */
    public function daily(): array
    {
        return [
            // Proyectos y repositorios con más de 30 días en la papelera
            'trash_purged' => $this->trash->purgeExpired(),
            // Invitaciones caducadas hace más de 30 días (las aceptadas se conservan como registro)
            'invitations_deleted' => ProjectInvitation::whereNull('accepted_at')
                ->where('expires_at', '<', now()->subDays(30))->delete(),
            // Sesiones web caducadas (en Vercel no hay recogida de basura fiable entre peticiones)
            'sessions_deleted' => config('session.driver') === 'database'
                ? DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                    ->where('last_activity', '<', now()->subMinutes((int) config('session.lifetime', 120))->timestamp)->delete()
                : 0,
            // Apps conectadas (tokens de la CLI, escritorio o Android) sin usarse en 6 meses
            'tokens_deleted' => DB::table('personal_access_tokens')
                ->where(fn ($q) => $q->where('last_used_at', '<', now()->subDays(self::TOKEN_IDLE_DAYS))
                    ->orWhere(fn ($q) => $q->whereNull('last_used_at')->where('created_at', '<', now()->subDays(self::TOKEN_IDLE_DAYS))))
                ->delete(),
            // Códigos de restablecer contraseña caducados
            'password_resets_deleted' => DB::table('password_reset_tokens')
                ->where('created_at', '<', now()->subMinutes((int) config('auth.passwords.users.expire', 60)))->delete(),
        ];
    }
}
