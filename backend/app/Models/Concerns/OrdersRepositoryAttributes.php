<?php

namespace App\Models\Concerns;

/**
 * Asegura que "repository_id" se asigna antes que el resto de atributos
 * en fill()/create(), porque los atributos cifrados lo necesitan.
 */
trait OrdersRepositoryAttributes
{
    public function fill(array $attributes)
    {
        if (array_key_exists('repository_id', $attributes)) {
            $attributes = ['repository_id' => $attributes['repository_id']] + $attributes;
        }

        return parent::fill($attributes);
    }
}
