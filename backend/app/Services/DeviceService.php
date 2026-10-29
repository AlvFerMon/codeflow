<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Dónde tiene abierta la cuenta el usuario: sesiones web (si las sesiones van en base de datos)
 * y tokens de las apps (CLI, app de escritorio, Android). Se pueden cerrar una a una o todas a la vez.
 */
class DeviceService
{
    /** @return array{web: Collection, apps: Collection, web_supported: bool} */
    public function list(User $user, ?string $currentSessionId = null, ?int $currentTokenId = null): array
    {
        $web = collect();
        if ($this->databaseSessions()) {
            $web = $this->sessions()->where('user_id', $user->id)->orderByDesc('last_activity')->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'kind' => 'web',
                    'name' => self::describeAgent((string) $s->user_agent),
                    'ip' => $s->ip_address,
                    'last_active_at' => Carbon::createFromTimestamp($s->last_activity),
                    'current' => $s->id === $currentSessionId,
                ]);
        }

        $apps = $user->tokens()->orderByDesc('last_used_at')->orderByDesc('created_at')->get()
            ->map(fn (PersonalAccessToken $t) => [
                'id' => $t->id,
                'kind' => self::appKind($t->name),
                'name' => self::appLabel($t->name),
                'created_at' => $t->created_at,
                'last_active_at' => $t->last_used_at ?? $t->created_at,
                'current' => $t->id === $currentTokenId,
            ]);

        return ['web' => $web, 'apps' => $apps, 'web_supported' => $this->databaseSessions()];
    }

    public function closeSession(User $user, string $id): bool
    {
        return $this->databaseSessions()
            && $this->sessions()->where('user_id', $user->id)->where('id', $id)->delete() > 0;
    }

    public function closeToken(User $user, int $id): bool
    {
        return $user->tokens()->whereKey($id)->delete() > 0;
    }

    /** Cierra todo menos la sesión o el token desde el que se pide. Devuelve cuántos se han cerrado. */
    public function closeOthers(User $user, ?string $keepSessionId, ?int $keepTokenId): int
    {
        $closed = $user->tokens()->when($keepTokenId, fn ($q) => $q->whereKeyNot($keepTokenId))->delete();

        if ($this->databaseSessions()) {
            $closed += $this->sessions()->where('user_id', $user->id)
                ->when($keepSessionId, fn ($q) => $q->where('id', '!=', $keepSessionId))->delete();
        }

        return $closed;
    }

    // ── Nombres legibles ───────────────────────────────────────────────────

    /** «Chrome en Windows», «Safari en iPhone»… a partir del User-Agent. */
    public static function describeAgent(string $agent): string
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') || str_contains($agent, 'CriOS/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            $agent === '' => 'Navegador desconocido',
            default => 'Navegador',
        };
        $system = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') => 'iPhone',
            str_contains($agent, 'iPad') => 'iPad',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS X') || str_contains($agent, 'Macintosh') => 'Mac',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return $system ? "{$browser} en {$system}" : $browser;
    }

    public static function appKind(string $name): string
    {
        return match (true) {
            str_starts_with($name, 'codeflow-desktop') => 'desktop',
            str_starts_with($name, 'codeflow-cli') => 'cli',
            str_starts_with($name, 'Android') => 'android',
            default => 'app',
        };
    }

    public static function appLabel(string $name): string
    {
        $host = preg_match('/\((.+)\)\s*$/', $name, $m) ? $m[1] : null;

        return match (self::appKind($name)) {
            'desktop' => 'App de escritorio'.($host ? " · {$host}" : ''),
            'cli' => 'Terminal (CLI)'.($host ? " · {$host}" : ''),
            'android' => 'App Android · '.trim(substr($name, strlen('Android'))),
            default => $name === 'web' ? 'Acceso por API' : $name,
        };
    }

    private function databaseSessions(): bool
    {
        return config('session.driver') === 'database';
    }

    private function sessions()
    {
        return DB::connection(config('session.connection'))->table(config('session.table', 'sessions'));
    }
}
