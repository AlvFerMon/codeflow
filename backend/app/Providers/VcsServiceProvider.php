<?php

namespace App\Providers;

use App\Services\VcsService;
use App\Vcs\Crypto\KeyManager;
use App\Vcs\Crypto\RepositoryCipher;
use App\Vcs\ObjectStore;
use Illuminate\Support\ServiceProvider;

/** Registra el motor de control de versiones y su cifrado. */
class VcsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KeyManager::class, fn ($app) => new KeyManager(
            $app['config']->get('codeflow.master_key'),
            (int) $app['config']->get('codeflow.key_version', 1),
        ));

        // Una instancia por petición: la DEK descifrada se memoriza aquí
        $this->app->singleton(RepositoryCipher::class);
        $this->app->singleton(ObjectStore::class);
        $this->app->singleton(VcsService::class);
    }
}
