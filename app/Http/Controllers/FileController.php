<?php

namespace App\Http\Controllers;

use App\Models\Blob;
use App\Models\Branch;
use App\Services\VcsService;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileController extends Controller
{
    /**
     * Lista el contenido de una rama.
     * - Sin parámetros: entradas de la raíz (un nivel).
     * - ?path=src/app : entradas de esa carpeta (un nivel).
     * - ?recursive=1  : todos los archivos en una lista plana.
     */
    public function index(Request $request, Branch $branch, VcsService $vcs): JsonResponse
    {
        $this->authorizeRole($request, $branch->repository->project, Roles::OBSERVER);

        $tree = $vcs->tree($branch->head_commit_id);

        if ($request->boolean('recursive')) {
            $files = array_map(fn ($path) => ['name' => basename($path), 'path' => $path, 'type' => 'file'], array_keys($tree));
            sort($files);

            return response()->json($files);
        }

        $prefix = trim((string) $request->query('path', ''), '/');
        $prefix = $prefix === '' ? '' : $prefix.'/';
        $entries = [];

        foreach (array_keys($tree) as $path) {
            if ($prefix !== '' && ! str_starts_with($path, $prefix)) {
                continue;
            }
            $rest = substr($path, strlen($prefix));
            $segment = explode('/', $rest)[0];
            $isDir = str_contains($rest, '/');
            $key = ($isDir ? 'd:' : 'f:').$segment;

            $entries[$key] = [
                'name' => $segment,
                'path' => $prefix.$segment,
                'type' => $isDir ? 'dir' : 'file',
            ];
        }

        $entries = array_values($entries);
        usort($entries, fn ($a, $b) => [$a['type'] === 'file', $a['name']] <=> [$b['type'] === 'file', $b['name']]);

        return response()->json($entries);
    }

    /** Contenido de un archivo (JSON) o descarga con ?download=1 */
    public function show(Request $request, Branch $branch, VcsService $vcs)
    {
        $this->authorizeRole($request, $branch->repository->project, Roles::OBSERVER);

        $request->validate(['path' => 'required|string|max:255']);

        $path = $vcs->normalizePath($request->query('path'));
        $tree = $vcs->tree($branch->head_commit_id);

        abort_unless(isset($tree[$path]), 404, 'Archivo no encontrado en esta rama.');

        $blob = Blob::findOrFail($tree[$path]);

        if ($request->boolean('download')) {
            return response()->streamDownload(function () use ($blob) {
                echo $blob->content;
            }, basename($path));
        }

        return response()->json([
            'path' => $path,
            'size' => strlen($blob->content),
            'content' => $blob->content,
        ]);
    }
}
