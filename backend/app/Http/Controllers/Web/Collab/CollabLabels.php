<?php

namespace App\Http\Controllers\Web\Collab;

/**
 * Textos en español y colores (x-badge) de los estados de issues y Pull Requests.
 * Lo usan los controladores web (opciones de los formularios) y los componentes collab-*.
 */
final class CollabLabels
{
    public const ISSUE_STATUSES = [
        'open' => 'Abierta',
        'in_progress' => 'En progreso',
        'resolved' => 'Resuelta',
        'closed' => 'Cerrada',
    ];

    public const ISSUE_STATUS_COLORS = [
        'open' => 'green',
        'in_progress' => 'amber',
        'resolved' => 'purple',
        'closed' => 'slate',
    ];

    /** Pestañas del listado de issues. */
    public const ISSUE_OPEN_STATES = ['open', 'in_progress'];

    public const ISSUE_CLOSED_STATES = ['resolved', 'closed'];

    public const PRIORITIES = [
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
        'critical' => 'Crítica',
    ];

    public const PRIORITY_COLORS = [
        'low' => 'slate',
        'medium' => 'blue',
        'high' => 'amber',
        'critical' => 'red',
    ];

    public const PR_STATUSES = [
        'open' => 'Abierto',
        'in_review' => 'En revisión',
        'approved' => 'Aprobado',
        'changes_requested' => 'Cambios solicitados',
        'closed' => 'Cerrado',
        'merged' => 'Fusionado',
    ];

    public const PR_STATUS_COLORS = [
        'open' => 'green',
        'in_review' => 'amber',
        'approved' => 'green',
        'changes_requested' => 'red',
        'closed' => 'slate',
        'merged' => 'purple',
    ];

    public const PR_CLOSED_STATES = ['closed', 'merged'];

    public const REVIEW_STATES = [
        'approved' => 'Aprobado',
        'changes_requested' => 'Cambios solicitados',
        'commented' => 'Comentario',
    ];

    public const REVIEW_STATE_COLORS = [
        'approved' => 'green',
        'changes_requested' => 'red',
        'commented' => 'slate',
    ];

    public const ROLES = [
        'owner' => 'Propietario',
        'collaborator' => 'Colaborador',
        'observer' => 'Observador',
    ];

    /** Color estable para una etiqueta libre. */
    public static function labelColor(string $label): string
    {
        $special = ['bug' => 'red', 'seguridad' => 'red', 'urgente' => 'red', 'mejora' => 'green', 'documentación' => 'blue'];
        $key = mb_strtolower($label);

        if (isset($special[$key])) {
            return $special[$key];
        }

        $palette = ['indigo', 'blue', 'purple', 'amber', 'green', 'slate'];

        return $palette[crc32($key) % count($palette)];
    }
}
