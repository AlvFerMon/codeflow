<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // El administrador global puede hacerlo todo (las reglas de negocio de los servicios siguen aplicando)
        Gate::before(fn (?User $user) => $user?->isAdmin() ? true : null);

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
    }
}
