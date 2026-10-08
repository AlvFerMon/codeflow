<?php

namespace App\Http\Controllers\Web\Code;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Code\Concerns\InteractsWithCode;
use App\Models\Blob;
use App\Models\Commit;
use App\Models\Project;
use App\Models\Repository;
use App\Services\RepositoryService;
use App\Services\VcsService;
use App\Support\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/** Repositorios en la web: crear, explorar el código, ver archivos y configuración. */
class RepositoryController extends Controller
{
    use InteractsWithCode;

    /** Archivos de texto más grandes que esto no se muestran en la página (solo descarga). */
    private const MAX_RENDER_BYTES = 1024 * 1024;

    public function __construct(
        private RepositoryService $repositories,
        private VcsService $vcs,
    ) {}

    public function create(Project $project): View
    {
        Gate::authorize('create', [Repository::class, $project]);

        return view('repositories.create', ['project' => $project]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('create', [Repository::class, $project]);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('repositories')->where('project_id', $project->id),
            ],
            'description' => 'nullable|string|max:2000',
            'visibility' => ['required', Rule::in(Visibility::ALL)],
        ], $this->repositoryMessages(), ['name' => 'nombre', 'description' => 'descripción', 'visibility' => 'visibilidad']);

        $data['init_readme'] = $request->boolean('init_readme');

        $repository = $this->attempt(fn () => $this->repositories->create($project, $request->user(), $data));
        if ($this->failed($repository)) {
            return $repository;
        }

        return redirect()->route('repositories.show', $repository)->with('status', "Repositorio {$repository->name} creado.");
    }

    /** Explorador de código: carpeta ?path= de la rama ?branch=. */
    public function show(Request $request, Repository $repository): View|RedirectResponse
    {
        Gate::authorize('view', $repository);

        $branch = $this->resolveBranch($repository, $request->query('branch'));
        $path = $this->cleanPath($request->query('path'));
        $tree = $this->vcs->tree($branch?->head_commit_id);

        // Si la ruta es un archivo, se muestra el archivo
        if ($path !== '' && isset($tree[$path])) {
            return redirect()->route('repositories.blob', [$repository, 'branch' => $branch->name, 'path' => $path]);
        }
        abort_unless($this->directoryExists($tree, $path), 404, 'La carpeta no existe en esta rama.');

        $entries = $this->listDirectory($tree, $path);
        $sizes = Blob::whereIn('id', array_filter(array_column($entries, 'blob_id')))->pluck('size', 'id');

        $readme = null;
        foreach ($entries as $entry) {
            if ($entry['type'] === 'file' && preg_match('/^readme(\.md|\.markdown|\.txt)?$/i', $entry['name'])) {
                $file = $this->vcs->readFile($repository->id, $entry['blob_id']);
                if (! $file['binary'] && $file['size'] <= self::MAX_RENDER_BYTES) {
                    $readme = [
                        'name' => $entry['name'],
                        'path' => $entry['path'],
                        'content' => $file['content'],
                        'markdown' => (bool) preg_match('/\.(md|markdown)$/i', $entry['name']),
                    ];
                }
                break;
            }
        }

        $graph = $this->vcs->graph($repository->id);

        return view('repositories.show', [
            'repository' => $repository,
            'branch' => $branch,
            'branches' => $repository->branches()->orderBy('name')->pluck('name'),
            'path' => $path,
            'entries' => $entries,
            'sizes' => $sizes,
            'readme' => $readme,
            'lastCommit' => $branch?->head_commit_id ? Commit::with('author:id,name')->find($branch->head_commit_id) : null,
            'commitsCount' => count($this->vcs->ancestors($graph, $branch?->head_commit_id)),
            'branchesCount' => $repository->branches()->count(),
        ]);
    }

    /** Vista de un archivo (?branch=&path=), con números de línea o renderizado si es Markdown. */
    public function blob(Request $request, Repository $repository): View
    {
        Gate::authorize('view', $repository);

        [$branch, $path, $blobId] = $this->locateFile($request, $repository);
        $file = $this->vcs->readFile($repository->id, $blobId);

        $isMarkdown = (bool) preg_match('/\.(md|markdown)$/i', $path);
        $tooLarge = ! $file['binary'] && $file['size'] > self::MAX_RENDER_BYTES;
        $lines = [];
        if (! $file['binary'] && ! $tooLarge && $file['content'] !== '') {
            $lines = preg_split('/\r\n|\n|\r/', $file['content']);
            if (end($lines) === '') {
                array_pop($lines);
            }
        }

        return view('repositories.blob', [
            'repository' => $repository,
            'branch' => $branch,
            'branches' => $repository->branches()->orderBy('name')->pluck('name'),
            'path' => $path,
            'file' => $file,
            'lines' => $lines,
            'tooLarge' => $tooLarge,
            'isMarkdown' => $isMarkdown,
            'rendered' => $isMarkdown && ! $tooLarge && ! $file['binary'] && $request->query('view') === 'rendered',
        ]);
    }

    /** Contenido en bruto. Con ?download=1 se descarga como adjunto. */
    public function raw(Request $request, Repository $repository): Response
    {
        Gate::authorize('view', $repository);

        [, $path, $blobId] = $this->locateFile($request, $repository);
        $file = $this->vcs->readFile($repository->id, $blobId);

        $filename = basename($path);
        $disposition = HeaderUtils::makeDisposition(
            $request->boolean('download') ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
            $filename,
            preg_replace('/[^\x20-\x7e]|[%\/\\\\]/', '_', $filename) ?: 'archivo'
        );

        return response($file['content'], 200, [
            'Content-Type' => $file['binary'] ? 'application/octet-stream' : 'text/plain; charset=utf-8',
            'Content-Disposition' => $disposition,
            'Content-Length' => (string) strlen($file['content']),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function settings(Repository $repository): View
    {
        Gate::authorize('update', $repository);

        return view('repositories.settings', [
            'repository' => $repository,
            'branches' => $repository->branches()->orderBy('name')->pluck('name', 'name')->all(),
        ]);
    }

    public function update(Request $request, Repository $repository): RedirectResponse
    {
        Gate::authorize('update', $repository);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('repositories')->where('project_id', $repository->project_id)->ignore($repository->id),
            ],
            'description' => 'nullable|string|max:2000',
            'visibility' => ['required', Rule::in(Visibility::ALL)],
            'default_branch' => [
                'required', 'string',
                Rule::exists('branches', 'name')->where('repository_id', $repository->id),
            ],
        ], $this->repositoryMessages() + [
            'default_branch.exists' => 'La rama seleccionada no existe.',
        ], ['name' => 'nombre', 'description' => 'descripción', 'visibility' => 'visibilidad', 'default_branch' => 'rama por defecto']);

        $result = $this->attempt(fn () => DB::transaction(function () use ($repository, $request, $data) {
            $this->repositories->update($repository, $request->user(), $data);
            $this->repositories->changeDefaultBranch($repository, $request->user(), $data['default_branch']);

            return $repository;
        }));
        if ($this->failed($result)) {
            return $result;
        }

        return redirect()->route('repositories.settings', $repository)->with('status', 'Cambios guardados.');
    }

    public function destroy(Request $request, Repository $repository): RedirectResponse
    {
        Gate::authorize('delete', $repository);

        $request->validate(
            ['confirm_name' => ['required', 'string', Rule::in([$repository->name])]],
            [
                'confirm_name.required' => 'Escribe el nombre del repositorio para confirmar.',
                'confirm_name.in' => 'El nombre escrito no coincide con el del repositorio.',
            ]
        );

        $project = $repository->project;
        $name = $repository->name;
        $this->repositories->delete($repository, $request->user());

        return redirect()->route('projects.show', $project)->with('status', "Repositorio {$name} eliminado.");
    }

    /**
     * Rama, ruta normalizada y blob del archivo pedido en ?branch=&path=. 404 si no existe.
     *
     * @return array{0:\App\Models\Branch,1:string,2:int}
     */
    private function locateFile(Request $request, Repository $repository): array
    {
        $branch = $this->resolveBranch($repository, $request->query('branch'));
        $path = $this->cleanPath($request->query('path'));
        abort_if(! $branch || $path === '', 404);

        $tree = $this->vcs->tree($branch->head_commit_id);
        abort_unless(isset($tree[$path]), 404, 'El archivo no existe en esta rama.');

        return [$branch, $path, (int) $tree[$path]];
    }

    /** @return array<string,string> */
    private function repositoryMessages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'name.regex' => 'El nombre solo puede contener letras, números, puntos, guiones y guiones bajos.',
            'name.unique' => 'Ya existe un repositorio con ese nombre en el proyecto.',
            'description.max' => 'La descripción no puede superar los 2000 caracteres.',
            'visibility.required' => 'Elige la visibilidad del repositorio.',
            'visibility.in' => 'Visibilidad no válida.',
        ];
    }
}
