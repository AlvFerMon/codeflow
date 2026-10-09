<?php

namespace App\Http\Controllers\Web\Code\Concerns;

use App\Models\Branch;
use App\Models\Commit;
use App\Models\Repository;
use Closure;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Utilidades comunes de las páginas de código (ramas, árbol y errores de los servicios). */
trait InteractsWithCode
{
    /** Archivos que solo sirven para que exista una carpeta vacía: no se muestran. */
    protected const PLACEHOLDER_FILES = ['.keep', '.gitkeep'];

    /** Rama pedida (?branch=) o la rama por defecto. 404 si se pide una que no existe. */
    protected function resolveBranch(Repository $repository, ?string $name): ?Branch
    {
        $name = $name !== null && $name !== '' ? $name : $repository->default_branch;

        $branch = Branch::where('repository_id', $repository->id)->where('name', $name)->first();

        abort_if(! $branch && $name !== $repository->default_branch, 404, 'La rama no existe.');

        // Si la rama por defecto no existe se usa la primera que haya
        return $branch ?? Branch::where('repository_id', $repository->id)->orderBy('id')->first();
    }

    /**
     * Versión del código que se muestra: un commit concreto (?commit=sha, solo lectura) o la
     * cabeza de la rama (?branch=). Devuelve [rama, commit fijado o null, id del commit a mostrar].
     *
     * @return array{0:?Branch,1:?Commit,2:?int}
     */
    protected function resolveRef(Repository $repository, ?string $branchName, ?string $sha): array
    {
        if ($sha !== null && $sha !== '') {
            abort_unless(preg_match('/^[0-9a-f]{7,64}$/i', $sha), 404);
            $matches = Commit::where('repository_id', $repository->id)->where('sha', 'like', strtolower($sha).'%')->limit(2)->get();
            abort_unless($matches->count() === 1, 404, 'Commit no encontrado.');
            $commit = $matches->first()->load('author:id,name');

            return [$this->resolveBranch($repository, null), $commit, $commit->id];
        }

        $branch = $this->resolveBranch($repository, $branchName);

        return [$branch, null, $branch?->head_commit_id];
    }

    /** Ruta de carpeta/archivo recibida por la URL, sin barras sobrantes. */
    protected function cleanPath(?string $path): string
    {
        return trim(str_replace('\\', '/', (string) $path), '/');
    }

    /**
     * Contenido de una carpeta (un nivel): carpetas primero y después archivos.
     *
     * @param  array<string,int>  $tree  ruta => blob_id
     * @return array<int,array{name:string,path:string,type:string,blob_id:?int}>
     */
    protected function listDirectory(array $tree, string $directory): array
    {
        $prefix = $directory === '' ? '' : $directory.'/';
        $entries = [];

        foreach ($tree as $path => $blobId) {
            $path = (string) $path;
            if ($prefix !== '' && ! str_starts_with($path, $prefix)) {
                continue;
            }
            $rest = substr($path, strlen($prefix));
            $segment = explode('/', $rest)[0];
            $isDir = str_contains($rest, '/');

            if (! $isDir && in_array($segment, self::PLACEHOLDER_FILES, true)) {
                continue;
            }

            $entries[($isDir ? 'd:' : 'f:').$segment] = [
                'name' => $segment,
                'path' => $prefix.$segment,
                'type' => $isDir ? 'dir' : 'file',
                'blob_id' => $isDir ? null : $blobId,
            ];
        }

        $entries = array_values($entries);
        usort($entries, fn ($a, $b) => [$a['type'] === 'file', strtolower($a['name'])] <=> [$b['type'] === 'file', strtolower($b['name'])]);

        return $entries;
    }

    /** ¿Existe la carpeta en el árbol (algún archivo bajo ese prefijo)? */
    protected function directoryExists(array $tree, string $directory): bool
    {
        if ($directory === '') {
            return true;
        }

        foreach (array_keys($tree) as $path) {
            if (str_starts_with((string) $path, $directory.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ejecuta una acción de un servicio y convierte sus errores de negocio
     * (abort 4xx: 422, 409, 404 de validación...) en un mensaje flash en vez de una página de error.
     */
    protected function attempt(Closure $action, ?string $backTo = null): mixed
    {
        try {
            return $action();
        } catch (HttpException $e) {
            if (! in_array($e->getStatusCode(), [409, 422], true)) {
                throw $e;
            }

            $redirect = $backTo ? redirect()->to($backTo) : back();

            return $redirect->withInput()->with('error', $e->getMessage() ?: 'No se pudo completar la acción.');
        }
    }

    /** Respuesta de redirección devuelta por attempt() cuando el servicio falló. */
    protected function failed(mixed $result): bool
    {
        return $result instanceof RedirectResponse;
    }
}
