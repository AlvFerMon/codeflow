<?php

namespace App\Support;

use App\Models\Repository;

/**
 * Visibilidad de los repositorios.
 *
 * Alias de Repository::VISIBILITY_* / Repository::isPublic() para validaciones y consultas.
 */
final class Visibility
{
    public const PRIVATE = Repository::VISIBILITY_PRIVATE;

    public const PUBLIC = Repository::VISIBILITY_PUBLIC;

    public const ALL = Repository::VISIBILITIES;

    public static function isPublic(Repository $repository): bool
    {
        return $repository->isPublic();
    }
}
