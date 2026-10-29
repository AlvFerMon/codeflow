<?php

namespace App\Providers;

use App\Ai\AiReviewer;
use App\Ai\NullReviewer;
use App\Ai\OllamaReviewer;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Revisión con IA (opcional): Ollama si está activada; si no, un revisor nulo
        $this->app->bind(AiReviewer::class, fn () => config('codeflow.ai.enabled')
            ? new OllamaReviewer(
                url: (string) config('codeflow.ai.url'),
                model: (string) config('codeflow.ai.model'),
                timeout: (int) config('codeflow.ai.timeout'),
                context: (int) config('codeflow.ai.context'),
            )
            : new NullReviewer);
    }

    public function boot(): void
    {
        // Límites propios de la verificación del email (con clave por acción para no compartir el contador)
        RateLimiter::for('verification-code', fn (Request $request) => Limit::perMinute(10)->by('verify-code:'.($request->user()?->id ?? $request->ip())));
        // Explorar: límite solo para visitantes (los usuarios con sesión ya están identificados)
        RateLimiter::for('explore', fn (Request $request) => $request->user() ? Limit::none() : Limit::perMinute(120)->by('explore:'.$request->ip()));
        RateLimiter::for('verification-send', fn (Request $request) => Limit::perMinute(3)->by('verify-send:'.($request->user()?->id ?? $request->ip())));

        // El administrador global puede hacerlo todo (las reglas de negocio de los servicios siguen aplicando)
        Gate::before(fn (?User $user) => $user?->isAdmin() && $user->hasVerifiedEmail() ? true : null);

        // Los tokens de usuarios desactivados dejan de ser válidos
        Sanctum::authenticateAccessTokensUsing(
            fn (PersonalAccessToken $token, bool $isValid) => $isValid
                && ! ($token->tokenable instanceof User && ! $token->tokenable->isActive())
        );

        // El enlace del correo apunta a la web de CodeFlow (APP_URL) salvo que se configure
        // otro frontend con FRONTEND_URL (p. ej. un deep link de Android)
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $base = rtrim(config('app.frontend_url') ?: config('app.url'), '/');

            return $base.'/reset-password?token='.$token.'&email='.urlencode($user->getEmailForPasswordReset());
        });

        // Correo de restablecer contraseña en español (el de verificación es App\Notifications\VerificationCode)
        ResetPassword::toMailUsing(fn ($user, string $token) => (new MailMessage)
            ->subject('Restablece tu contraseña de CodeFlow')
            ->greeting('¡Hola, '.$user->name.'!')
            ->line('Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Elegir una contraseña nueva', call_user_func(ResetPassword::$createUrlCallback, $user, $token))
            ->line('El enlace caduca en '.config('auth.passwords.users.expire', 60).' minutos.')
            ->line('Si no lo has pedido tú, no tienes que hacer nada: tu contraseña no cambiará.')
            ->salutation('El equipo de CodeFlow'));
    }
}
