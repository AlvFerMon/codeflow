<?php

namespace App\Http\Controllers\Web\Collab\Concerns;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Los servicios señalan los errores de negocio con abort(422/409...). En la web
 * se convierten en un mensaje flash de error en lugar de una página de error.
 */
trait HandlesServiceErrors
{
    /** Mensajes de validación en español (el proyecto no incluye lang/es). */
    protected const VALIDATION_MESSAGES = [
        'required' => 'El campo :attribute es obligatorio.',
        'required_if' => 'El campo :attribute es obligatorio en este caso.',
        'string' => 'El campo :attribute debe ser texto.',
        'integer' => 'El campo :attribute debe ser un número entero.',
        'min' => 'El campo :attribute debe ser al menos :min.',
        'max' => 'El campo :attribute es demasiado largo (máximo :max).',
        'array' => 'El campo :attribute no es válido.',
        'in' => 'El valor de :attribute no es válido.',
        'exists' => 'El valor de :attribute no existe.',
        'different' => 'La rama origen y la de destino deben ser distintas.',
    ];

    protected const VALIDATION_ATTRIBUTES = [
        'title' => 'título',
        'description' => 'descripción',
        'body' => 'comentario',
        'priority' => 'prioridad',
        'status' => 'estado',
        'assignee_id' => 'persona asignada',
        'labels' => 'etiquetas',
        'labels.*' => 'etiqueta',
        'path' => 'archivo',
        'line' => 'línea',
        'state' => 'tipo de revisión',
        'source_branch_id' => 'rama origen',
        'target_branch_id' => 'rama destino',
        'reviewer_ids' => 'revisores',
        'reviewer_ids.*' => 'revisor',
        'issue_ids' => 'issues vinculadas',
        'issue_ids.*' => 'issue',
    ];

    /**
     * Ejecuta la acción del servicio; si aborta, vuelve atrás con el mensaje de error.
     *
     * @param  Closure(): mixed  $action
     * @param  Closure(mixed): RedirectResponse  $onSuccess
     */
    protected function attempt(Closure $action, Closure $onSuccess): RedirectResponse
    {
        try {
            $result = $action();
        } catch (HttpResponseException $e) {
            return $this->failed($this->messageFromResponse($e->getResponse()));
        } catch (HttpException $e) {
            return $this->failed($e->getMessage());
        }

        return $onSuccess($result);
    }

    protected function failed(?string $message): RedirectResponse
    {
        return back()->withInput()->with('error', $message ?: 'No se ha podido completar la acción.');
    }

    /** Respuestas JSON de abort(response()->json(...)), p. ej. el 409 de conflictos de fusión. */
    private function messageFromResponse($response): string
    {
        if (! $response instanceof JsonResponse) {
            return 'No se ha podido completar la acción.';
        }

        $data = $response->getData(true);
        $message = $data['message'] ?? 'No se ha podido completar la acción.';

        if (! empty($data['conflicts'])) {
            $message .= ' Archivos en conflicto: '.implode(', ', $data['conflicts']).'.';
        }

        return $message;
    }

    /** "bug, seguridad ,  bug" → ['bug', 'seguridad'] */
    protected function parseLabels(?string $labels): array
    {
        return collect(explode(',', (string) $labels))
            ->map(fn ($label) => trim($label))
            ->filter(fn ($label) => $label !== '')
            ->unique(fn ($label) => mb_strtolower($label))
            ->values()
            ->all();
    }
}
