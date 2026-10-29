<?php

namespace App\Models\Concerns;

/**
 * Las URLs de ramas, commits, issues y PRs dan 404 si su repositorio está en la papelera
 * (el repositorio usa SoftDeletes, así que whereHas ya lo excluye).
 */
trait BelongsToActiveRepository
{
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->resolveRouteBindingQuery($this, $value, $field)->whereHas('repository')->first();
    }
}
