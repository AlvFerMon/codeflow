<?php

namespace App\Support;

final class Roles
{
    public const OWNER = 'owner';

    public const COLLABORATOR = 'collaborator';

    public const OBSERVER = 'observer';

    /** Roles que se pueden asignar al invitar (el propietario es único). */
    public const ASSIGNABLE = [self::COLLABORATOR, self::OBSERVER];

    public static function level(?string $role): int
    {
        return match ($role) {
            self::OWNER => 3,
            self::COLLABORATOR => 2,
            self::OBSERVER => 1,
            default => 0,
        };
    }

    /** Nombre del rol en español para mensajes. */
    public static function label(?string $role): string
    {
        return match ($role) {
            self::OWNER => 'propietario',
            self::COLLABORATOR => 'colaborador',
            self::OBSERVER => 'observador',
            default => 'sin rol',
        };
    }
}
