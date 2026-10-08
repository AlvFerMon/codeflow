<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // API sin vistas: el enlace del correo apunta al frontend (web o deep link de Android)
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $base = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');

            return $base.'/reset-password?token='.$token.'&email='.urlencode($user->getEmailForPasswordReset());
        });
    }
}
