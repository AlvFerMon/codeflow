<?php

namespace App\Http\Controllers;

use App\Models\Blob;
use App\Models\Branch;
use App\Models\Commit;
use App\Services\VcsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
        Gate::authorize('view', $branch->repository);

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
        Gate::authorize('view', $branch->repository);

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

        return response()->json(['path' => $path] + self::encode((string) $blob->content, (bool) $blob->is_binary));
    }

    /**
     * Manifiesto de una rama para la CLI: commit actual y, por archivo, el id de su versión (blob).
     * Comparándolo con el último manifiesto descargado, la CLI sabe qué archivos han cambiado.
     */
    public function manifest(Request $request, Branch $branch, VcsService $vcs): JsonResponse
    {
        Gate::authorize('view', $branch->repository);

        // ?commit_id=: manifiesto de un commit concreto del repositorio (p. ej. el que acaba de subir la CLI)
        $commitId = $request->filled('commit_id') ? (int) $request->query('commit_id') : $branch->head_commit_id;
        if ($commitId !== $branch->head_commit_id) {
            abort_unless(Commit::whereKey($commitId)->where('repository_id', $branch->repository_id)->exists(), 404);
        }
        $tree = $vcs->tree($commitId);
        $meta = [];
        foreach (array_chunk(array_values(array_unique($tree)), 500) as $ids) {
            foreach (Blob::whereIn('id', $ids)->get(['id', 'size', 'is_binary']) as $blob) {
                $meta[$blob->id] = $blob;
            }
        }

        $files = [];
        foreach ($tree as $path => $blobId) {
            $files[] = [
                'path' => (string) $path,
                'blob_id' => $blobId,
                'size' => (int) ($meta[$blobId]->size ?? 0),
                'binary' => (bool) ($meta[$blobId]->is_binary ?? false),
            ];
        }

        return response()->json([
            'branch' => $branch->name,
            'head_commit_id' => $branch->head_commit_id,
            'commit_id' => $commitId,
            'files' => $files,
        ]);
    }

    /**
     * Contenido de varios archivos de un commit en una sola petición (pull/clone de la CLI).
     * Se corta al llegar a ~3 MB: los que no caben vuelven en "remaining" para pedirlos después.
     */
    public function batch(Request $request, Branch $branch, VcsService $vcs): JsonResponse
    {
        Gate::authorize('view', $branch->repository);

        $data = $request->validate([
            'paths' => 'required|array|min:1|max:500',
            'paths.*' => 'required|string|max:255',
            'commit_id' => 'nullable|integer',
        ]);

        $commitId = $data['commit_id'] ?? $branch->head_commit_id;
        if ($commitId !== null && $commitId !== $branch->head_commit_id) {
            abort_unless(Commit::whereKey($commitId)->where('repository_id', $branch->repository_id)->exists(), 404);
        }
        $tree = $vcs->tree($commitId);

        $budget = 3 * 1024 * 1024;
        $files = [];
        $remaining = [];
        foreach ($data['paths'] as $path) {
            $path = $vcs->normalizePath($path);
            if (! isset($tree[$path])) {
                abort(404, "Archivo no encontrado: {$path}");
            }
            if ($budget <= 0) {
                $remaining[] = $path;

                continue;
            }
            $file = $vcs->readFile($branch->repository_id, $tree[$path]);
            $encoded = self::encode($file['content'], $file['binary']);
            $budget -= strlen($encoded['content']);
            $files[] = ['path' => $path, 'blob_id' => $tree[$path]] + $encoded;
        }

        return response()->json(['commit_id' => $commitId, 'files' => $files, 'remaining' => $remaining]);
    }

    /** Los binarios viajan en base64 (JSON solo admite texto UTF-8). */
    private static function encode(string $content, bool $binary): array
    {
        return $binary
            ? ['size' => strlen($content), 'encoding' => 'base64', 'content' => base64_encode($content)]
            : ['size' => strlen($content), 'encoding' => 'utf8', 'content' => $content];
    }
}
