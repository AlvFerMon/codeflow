<?php

namespace App\Casts;

use App\Vcs\Crypto\RepositoryCipher;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Cifra el atributo con la clave del repositorio al que pertenece el modelo.
 *
 * En la BD se guarda el texto cifrado; el modelo (y su JSON) devuelve el texto
 * en claro. Requiere que el modelo tenga "repository_id" antes de asignar el
 * valor: el trait OrdersRepositoryAttributes lo garantiza en create()/fill().
 *
 * @implements CastsAttributes<string, string>
 */
class RepositoryEncrypted implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return app(RepositoryCipher::class)->decrypt($this->repositoryId($model, $key, $attributes), $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        return [$key => app(RepositoryCipher::class)->encrypt($this->repositoryId($model, $key, $attributes), (string) $value)];
    }

    private function repositoryId(Model $model, string $key, array $attributes): int
    {
        $repositoryId = $attributes['repository_id'] ?? null;

        if (! $repositoryId) {
            throw new LogicException(sprintf(
                'No se puede cifrar/descifrar %s::$%s sin "repository_id": asígnalo antes y selecciona esa columna en las consultas.',
                $model::class,
                $key
            ));
        }

        return (int) $repositoryId;
    }
}
