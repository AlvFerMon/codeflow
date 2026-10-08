<?php

namespace App\Http\Controllers\Web\Code;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Code\Concerns\InteractsWithCode;
use App\Models\Branch;
use App\Models\Repository;
use App\Services\RepositoryService;
use App\Services\VcsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Cambios en el código desde la web: subir archivos, crear archivos y carpetas,
 * editar y eliminar. Cada acción es un commit (RepositoryService::commitChanges).
 */
class FileController extends Controller
{
    use InteractsWithCode;

    public const MODES = ['upload', 'new', 'folder'];

    public function __construct(
        private RepositoryService $repositories,
        private VcsService $vcs,
    ) {}

    /** Formulario: ?mode=upload|new|folder&branch=&path=(carpeta destino) */
    public function create(Request $request, Repository $repository): View
    {
        Gate::authorize('push', $repository);

        $branch = $this->resolveBranch($repository, $request->query('branch'));
        abort_unless($branch, 404);
        $mode = in_array($request->query('mode'), self::MODES, true) ? $request->query('mode') : 'upload';

        return view('files.form', [
            'repository' => $repository,
            'branch' => $branch,
            'mode' => $mode,
            'directory' => $this->cleanPath($request->query('path')),
            'limits' => $this->limits(),
        ]);
    }

    /** Editor de un archivo de texto (?branch=&path=). */
    public function edit(Request $request, Repository $repository): View|RedirectResponse
    {
        Gate::authorize('push', $repository);

        $branch = $this->resolveBranch($repository, $request->query('branch'));
        $path = $this->cleanPath($request->query('path'));
        $tree = $this->vcs->tree($branch?->head_commit_id);
        abort_unless($path !== '' && isset($tree[$path]), 404, 'El archivo no existe en esta rama.');

        $file = $this->vcs->readFile($repository->id, (int) $tree[$path]);
        if ($file['binary']) {
            return redirect()->route('repositories.blob', [$repository, 'branch' => $branch->name, 'path' => $path])
                ->with('error', 'Los archivos binarios no se pueden editar en la web. Sube una versión nueva.');
        }

        return view('files.form', [
            'repository' => $repository,
            'branch' => $branch,
            'mode' => 'edit',
            'directory' => str_contains($path, '/') ? dirname($path) : '',
            'path' => $path,
            'blobId' => (int) $tree[$path],
            'content' => $file['content'],
            'limits' => $this->limits(),
        ]);
    }

    /** Guarda un commit de subida, archivo nuevo, carpeta nueva o edición. */
    public function store(Request $request, Repository $repository): RedirectResponse|JsonResponse
    {
        Gate::authorize('push', $repository);

        $request->validate([
            'mode' => ['required', Rule::in([...self::MODES, 'edit'])],
            'branch' => 'required|string|max:150',
            'message' => 'nullable|string|max:500',
        ], ['message.max' => 'El mensaje del commit no puede superar los 500 caracteres.']);

        $branch = $this->resolveBranch($repository, $request->input('branch'));
        abort_unless($branch, 404);

        [$changes, $defaultMessage, $redirect] = match ($request->input('mode')) {
            'upload' => $this->uploadChanges($request, $repository, $branch),
            'new' => $this->newFileChanges($request, $repository, $branch),
            'folder' => $this->folderChanges($request, $repository, $branch),
            'edit' => $this->editChanges($request, $repository, $branch),
        };

        $message = trim((string) $request->input('message')) ?: $defaultMessage;

        $commit = $this->attempt(fn () => $this->repositories->commitChanges($branch, $request->user(), $message, $changes));
        if ($this->failed($commit)) {
            // La subida con barra de progreso (XHR) espera JSON en lugar de una redirección
            return $request->expectsJson()
                ? response()->json(['message' => session()->pull('error') ?? 'No se pudo completar la acción.'], 422)
                : $commit;
        }

        $status = 'Cambios guardados en la rama '.$branch->name.' (commit '.substr($commit->sha, 0, 7).').';

        if ($request->expectsJson()) {
            session()->flash('status', $status);

            return response()->json(['redirect' => $redirect, 'message' => $status]);
        }

        return redirect($redirect)->with('status', $status);
    }

    /** Elimina un archivo o una carpeta entera (todos los archivos bajo ese prefijo) con un commit. */
    public function destroy(Request $request, Repository $repository): RedirectResponse
    {
        Gate::authorize('push', $repository);

        $request->validate([
            'branch' => 'required|string|max:150',
            'path' => 'required|string|max:255',
            'message' => 'nullable|string|max:500',
        ], ['message.max' => 'El mensaje del commit no puede superar los 500 caracteres.']);

        $branch = $this->resolveBranch($repository, $request->input('branch'));
        $path = $this->cleanPath($request->input('path'));
        $tree = $this->vcs->tree($branch?->head_commit_id);

        if (isset($tree[$path])) {
            $paths = [$path];
        } else {
            $paths = array_values(array_filter(array_map('strval', array_keys($tree)), fn ($p) => str_starts_with($p, $path.'/')));
        }
        abort_if($path === '' || ! $paths, 404, 'El archivo no existe en esta rama.');

        $isFolder = ! isset($tree[$path]);
        $message = trim((string) $request->input('message')) ?: ($isFolder ? "Eliminar carpeta {$path}" : "Eliminar {$path}");
        $changes = array_map(fn ($p) => ['path' => $p, 'delete' => true], $paths);

        $commit = $this->attempt(fn () => $this->repositories->commitChanges($branch, $request->user(), $message, $changes));
        if ($this->failed($commit)) {
            return $commit;
        }

        $parent = str_contains($path, '/') ? dirname($path) : '';
        $remaining = $this->vcs->tree($branch->head_commit_id);
        while ($parent !== '' && ! $this->directoryExists($remaining, $parent)) {
            $parent = str_contains($parent, '/') ? dirname($parent) : '';
        }

        return redirect()->route('repositories.show', array_filter([$repository, 'branch' => $branch->name, 'path' => $parent]))
            ->with('status', ($isFolder ? "Carpeta {$path} eliminada" : "Archivo {$path} eliminado").' (commit '.substr($commit->sha, 0, 7).').');
    }

    /**
     * Subida de archivos y carpetas completas. Cada archivo files[i] puede llevar en paths[i]
     * su ruta relativa (p. ej. "src/app/Main.php" al subir una carpeta); si no, se usa su nombre.
     * El contenido se lee después, archivo a archivo, dentro del commit ('source').
     *
     * @return array{0:array,1:string,2:string}
     */
    private function uploadChanges(Request $request, Repository $repository, Branch $branch): array
    {
        $limits = $this->limits();

        $request->validate([
            'directory' => 'nullable|string|max:255',
            'files' => 'required|array|min:1|max:'.$limits['files'],
            'files.*' => 'file|max:'.max(1, intdiv($limits['bytes'], 1024)),
            'paths' => 'nullable|array',
            'paths.*' => 'nullable|string|max:255',
        ], [
            'files.required' => 'Selecciona al menos un archivo o una carpeta.',
            'files.max' => 'Puedes subir como máximo :max archivos a la vez.',
            'files.*.file' => 'No se pudo subir el archivo :position (¿supera el tamaño máximo?).',
            'files.*.uploaded' => 'No se pudo subir el archivo :position (¿supera el tamaño máximo?).',
            'files.*.max' => 'Cada archivo puede ocupar como máximo '.$limits['label'].'.',
        ]);

        $files = array_values($request->file('files'));
        $total = array_sum(array_map(fn ($file) => (int) $file->getSize(), $files));
        if ($total > $limits['total_bytes']) {
            throw ValidationException::withMessages([
                'files' => 'La subida ocupa '.round($total / 1048576, 1).' MB y el máximo por subida es '.$limits['total_label'].'.',
            ]);
        }

        $directory = $this->cleanPath($request->input('directory'));
        $relative = array_values((array) $request->input('paths', []));
        $changes = [];
        foreach ($files as $i => $file) {
            $name = trim(str_replace('\\', '/', (string) ($relative[$i] ?? '')), '/');
            if ($name === '') {
                $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
            }
            $path = ($directory === '' ? '' : $directory.'/').$name;

            // Si se repite una ruta en la misma subida, gana la última
            $changes[$path] = ['path' => $path, 'source' => $file->getRealPath()];
        }
        $changes = array_values($changes);

        $folders = array_unique(array_filter(array_map(fn ($c) => str_contains($c['path'], '/') ? dirname($c['path']) : '', $changes)));
        $message = count($changes) === 1
            ? 'Subir '.$changes[0]['path']
            : 'Subir '.count($changes).' archivos'.(count($folders) > 1 ? ' en '.count($folders).' carpetas' : '');
        $redirect = route('repositories.show', array_filter([$repository, 'branch' => $branch->name, 'path' => $directory]));

        return [$changes, $message, $redirect];
    }

    /** @return array{0:array,1:string,2:string} */
    private function newFileChanges(Request $request, Repository $repository, Branch $branch): array
    {
        $request->validate([
            'directory' => 'nullable|string|max:255',
            'filename' => 'required|string|max:255',
            'file_content' => ['nullable', 'string', $this->maxBytesRule()],
        ], ['filename.required' => 'Escribe el nombre del archivo.']);

        $path = $this->joinPath($request->input('directory'), $request->input('filename'));
        $this->ensureFree($branch, $path, 'filename');

        return [
            [['path' => $path, 'content' => $this->normalizeNewlines((string) $request->input('file_content'))]],
            "Crear {$path}",
            route('repositories.blob', [$repository, 'branch' => $branch->name, 'path' => $path]),
        ];
    }

    /** @return array{0:array,1:string,2:string} */
    private function folderChanges(Request $request, Repository $repository, Branch $branch): array
    {
        $request->validate([
            'directory' => 'nullable|string|max:255',
            'folder' => 'required|string|max:200',
        ], ['folder.required' => 'Escribe el nombre de la carpeta.']);

        $folder = $this->joinPath($request->input('directory'), $request->input('folder'));
        $tree = $this->vcs->tree($branch->head_commit_id);
        if (isset($tree[$folder]) || $this->directoryExists($tree, $folder)) {
            throw ValidationException::withMessages(['folder' => 'Ya existe un archivo o carpeta con ese nombre.']);
        }

        return [
            [['path' => $folder.'/.keep', 'content' => '']],
            "Crear carpeta {$folder}",
            route('repositories.show', [$repository, 'branch' => $branch->name, 'path' => $folder]),
        ];
    }

    /** @return array{0:array,1:string,2:string} */
    private function editChanges(Request $request, Repository $repository, Branch $branch): array
    {
        $request->validate([
            'original_path' => 'required|string|max:255',
            'original_blob' => 'nullable|integer',
            'filename' => 'required|string|max:255',
            'directory' => 'nullable|string|max:255',
            'file_content' => ['nullable', 'string', $this->maxBytesRule()],
        ], ['filename.required' => 'Escribe el nombre del archivo.']);

        $original = $this->cleanPath($request->input('original_path'));
        $tree = $this->vcs->tree($branch->head_commit_id);
        abort_unless(isset($tree[$original]), 404, 'El archivo no existe en esta rama.');

        // Bloqueo optimista: si alguien cambió el archivo mientras se editaba, no se pisa su versión
        if ($request->filled('original_blob') && (int) $request->input('original_blob') !== (int) $tree[$original]) {
            throw ValidationException::withMessages([
                'file_content' => 'El archivo ha cambiado en la rama mientras lo editabas. Copia tus cambios y vuelve a abrir el editor.',
            ]);
        }

        $path = $this->joinPath($request->input('directory'), $request->input('filename'));
        $originalContent = $this->vcs->readFile($repository->id, (int) $tree[$original])['content'];
        $content = (string) $request->input('file_content');
        // Los navegadores envían los textarea con CRLF: se respeta el salto de línea del archivo original
        if (! str_contains($originalContent, "\r\n")) {
            $content = $this->normalizeNewlines($content);
        }

        $changes = [['path' => $path, 'content' => $content]];
        if ($path !== $original) {
            $this->ensureFree($branch, $path, 'filename');
            array_unshift($changes, ['path' => $original, 'delete' => true]);
        }

        return [
            $changes,
            $path !== $original ? "Renombrar {$original} a {$path}" : "Actualizar {$path}",
            route('repositories.blob', [$repository, 'branch' => $branch->name, 'path' => $path]),
        ];
    }

    /** Une carpeta y nombre y valida la ruta con el motor (VcsService::normalizePath). */
    private function joinPath(?string $directory, ?string $name): string
    {
        $directory = $this->cleanPath($directory);
        $joined = ($directory === '' ? '' : $directory.'/').trim((string) $name);

        try {
            return $this->vcs->normalizePath($joined);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw ValidationException::withMessages(['filename' => $e->getMessage()]);
        }
    }

    private function ensureFree(Branch $branch, string $path, string $field): void
    {
        $tree = $this->vcs->tree($branch->head_commit_id);
        if (isset($tree[$path]) || $this->directoryExists($tree, $path)) {
            throw ValidationException::withMessages([$field => "Ya existe un archivo o carpeta en {$path}."]);
        }
    }

    private function normalizeNewlines(string $content): string
    {
        return str_replace("\r\n", "\n", $content);
    }

    /** Regla: el contenido no puede superar el tamaño máximo por archivo (en bytes). */
    private function maxBytesRule(): \Closure
    {
        $limits = $this->limits();

        return function (string $attribute, mixed $value, \Closure $fail) use ($limits) {
            if (is_string($value) && strlen($value) > $limits['bytes']) {
                $fail('El contenido supera el tamaño máximo permitido por archivo ('.$limits['label'].').');
            }
        };
    }

    /**
     * Límites efectivos para subir archivos: los de config('codeflow.limits')
     * y los de PHP (upload_max_filesize, max_file_uploads), el menor de cada uno.
     *
     * @return array{bytes:int,upload_bytes:int,files:int,total_bytes:int,label:string,upload_label:string,total_label:string}
     */
    private function limits(): array
    {
        $bytes = (int) config('codeflow.limits.max_file_bytes') ?: PHP_INT_MAX;
        $phpUpload = $this->iniBytes((string) ini_get('upload_max_filesize'));
        $uploadBytes = $phpUpload > 0 ? min($bytes, $phpUpload) : $bytes;

        $files = (int) config('codeflow.limits.max_files_per_commit') ?: 200;
        $phpFiles = (int) ini_get('max_file_uploads');
        if ($phpFiles > 0) {
            $files = min($files, $phpFiles);
        }

        $total = (int) config('codeflow.limits.max_upload_bytes') ?: PHP_INT_MAX;
        $phpPost = $this->iniBytes((string) ini_get('post_max_size'));
        $totalBytes = $phpPost > 0 ? min($total, $phpPost) : $total;

        return [
            'bytes' => $bytes,
            'upload_bytes' => $uploadBytes,
            'files' => $files,
            'total_bytes' => $totalBytes,
            'label' => round($bytes / 1048576, 2).' MB',
            'upload_label' => round($uploadBytes / 1048576, 2).' MB',
            'total_label' => round($totalBytes / 1048576, 2).' MB',
        ];
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
