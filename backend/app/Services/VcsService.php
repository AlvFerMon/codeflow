<?php

namespace App\Services;

use App\Models\Blob;
use App\Models\Branch;
use App\Models\Commit;
use App\Models\CommitFile;
use App\Models\PullRequest;
use App\Models\User;
use App\Vcs\CommitHasher;
use App\Vcs\Diff\HunkBuilder;
use App\Vcs\Diff\MyersDiff;
use App\Vcs\ObjectStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Fachada del motor de control de versiones sobre la base de datos.
 *
 * - Cada commit guarda un snapshot completo del árbol (path => blob).
 * - Los contenidos se cifran con la clave del repositorio y se deduplican
 *   por HMAC dentro del repositorio (App\Vcs\ObjectStore).
 * - El sha de un commit es el SHA-256 de su contenido (App\Vcs\CommitHasher).
 * - Una rama es un puntero a un commit (head_commit_id).
 * - El diff es de líneas con el algoritmo de Myers (App\Vcs\Diff).
 * - La fusión es a nivel de archivo (3-way merge); si ambos lados cambian
 *   el mismo archivo de forma distinta, se devuelve un conflicto (409).
 */
class VcsService
{
    private readonly ObjectStore $objects;

    private readonly CommitHasher $hasher;

    /**
     * Memoria por petición (el servicio es singleton): el grafo de cada repositorio y los últimos
     * árboles leídos. Los árboles de un commit nunca cambian; el grafo se invalida al crear commits.
     *
     * @var array<int,Collection>
     */
    private array $graphs = [];

    /** @var array<int,array<string,int>> */
    private array $trees = [];

    private const TREES_KEPT = 16;

    public function __construct(?ObjectStore $objects = null, ?CommitHasher $hasher = null)
    {
        $this->objects = $objects ?? app(ObjectStore::class);
        $this->hasher = $hasher ?? new CommitHasher;
    }

    /** @return array<string,int> path => blob_id */
    public function tree(?int $commitId): array
    {
        if (! $commitId) {
            return [];
        }

        if (! array_key_exists($commitId, $this->trees)) {
            if (count($this->trees) >= self::TREES_KEPT) {
                array_shift($this->trees); // se descarta el más antiguo (los árboles pueden ser grandes)
            }
            $this->trees[$commitId] = CommitFile::where('commit_id', $commitId)->pluck('blob_id', 'path')->all();
        }

        return $this->trees[$commitId];
    }

    /** Rutas de un commit que están dentro de una carpeta (filtrado en SQL, sin leer el árbol entero). */
    public function treeUnder(?int $commitId, string $directory): array
    {
        if (! $commitId) {
            return [];
        }
        if ($directory === '' || isset($this->trees[$commitId])) {
            $tree = $this->tree($commitId);

            return $directory === '' ? $tree : array_filter($tree, fn ($p) => str_starts_with($p, $directory.'/'), ARRAY_FILTER_USE_KEY);
        }

        // LIKE con '!' como carácter de escape (válido igual en MySQL y en SQLite)
        $prefix = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $directory).'/';

        return CommitFile::where('commit_id', $commitId)
            ->whereRaw("path LIKE ? ESCAPE '!'", [$prefix.'%'])
            ->pluck('blob_id', 'path')
            ->all();
    }

    /** Grafo mínimo de commits de un repositorio (id, parent_id, second_parent_id). */
    public function graph(int $repositoryId): Collection
    {
        // Objetos simples (no modelos Eloquent): recorrer el grafo es ~8 veces más rápido.
        // Se calcula una vez por petición aunque varias partes de la pantalla lo necesiten.
        return $this->graphs[$repositoryId] ??= Commit::where('repository_id', $repositoryId)
            ->toBase()
            ->get(['id', 'parent_id', 'second_parent_id'])
            ->keyBy('id');
    }

    /** Olvida el grafo memorizado (al crear commits o si se crean desde fuera del servicio). */
    public function forgetGraph(?int $repositoryId = null): void
    {
        if ($repositoryId === null) {
            $this->graphs = [];
        } else {
            unset($this->graphs[$repositoryId]);
        }
    }

    /** @return array<int,bool> ids de commits alcanzables desde $id (incluido) */
    public function ancestors(Collection $graph, ?int $id): array
    {
        $seen = [];
        $stack = $id ? [$id] : [];

        while ($stack) {
            $current = array_pop($stack);
            if (isset($seen[$current])) {
                continue;
            }
            $seen[$current] = true;
            $commit = $graph[$current] ?? null;
            if (! $commit) {
                continue;
            }
            foreach ([$commit->parent_id, $commit->second_parent_id] as $parent) {
                if ($parent) {
                    $stack[] = $parent;
                }
            }
        }

        return $seen;
    }

    /**
     * Historial de una rama: ids de los commits alcanzables desde $headId, del más
     * reciente al más antiguo. Con $path, solo los commits que cambiaron ese archivo
     * respecto a su primer padre (como `git log -- ruta`, simplificado).
     *
     * @return array<int,int>
     */
    public function historyIds(int $repositoryId, ?int $headId, ?string $path = null): array
    {
        $graph = $this->graph($repositoryId);
        $ids = array_keys($this->ancestors($graph, $headId));
        rsort($ids);

        if ($path === null || $path === '' || ! $ids) {
            return $ids;
        }

        $blobs = [];
        foreach (array_chunk($ids, 1000) as $chunk) {
            $blobs += CommitFile::whereIn('commit_id', $chunk)->where('path', $path)->pluck('blob_id', 'commit_id')->all();
        }

        return array_values(array_filter($ids, function (int $id) use ($graph, $blobs) {
            $parent = $graph[$id]->parent_id ?? null;

            return ($blobs[$id] ?? null) !== ($parent ? $blobs[$parent] ?? null : null);
        }));
    }

    public function mergeBase(Collection $graph, int $a, int $b): ?int
    {
        $ancestorsOfA = $this->ancestors($graph, $a);
        $queue = [$b];
        $visited = [];

        while ($queue) {
            $current = array_shift($queue);
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;
            if (isset($ancestorsOfA[$current])) {
                return $current;
            }
            $commit = $graph[$current] ?? null;
            if ($commit) {
                foreach ([$commit->parent_id, $commit->second_parent_id] as $parent) {
                    if ($parent) {
                        $queue[] = $parent;
                    }
                }
            }
        }

        return null;
    }

    public function normalizePath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', trim($path)), '/');

        if ($normalized === '' || strlen($normalized) > 255) {
            abort(422, "Ruta de archivo no válida: {$path}");
        }
        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                abort(422, "Ruta de archivo no válida: {$path}");
            }
        }

        return $normalized;
    }

    /** Guarda (o reutiliza si ya existe) el contenido cifrado de un archivo. */
    public function blob(int $repositoryId, string $content): Blob
    {
        return $this->objects->put($repositoryId, $content);
    }

    /**
     * Contenido en claro de un blob del repositorio.
     *
     * @return array{content:string,size:int,binary:bool}
     */
    public function readFile(int $repositoryId, int $blobId): array
    {
        return $this->objects->read($repositoryId, $blobId);
    }

    /** @param array<int,int> $parents @param array<string,int> $tree */
    public function createCommit(int $repositoryId, array $parents, array $tree, ?User $author, string $message): Commit
    {
        $parents = array_values($parents);
        $now = now();

        $commit = Commit::create([
            'repository_id' => $repositoryId,
            'parent_id' => $parents[0] ?? null,
            'second_parent_id' => $parents[1] ?? null,
            'author_id' => $author?->id,
            'sha' => $this->hasher->hash(
                $this->treeMacs($tree),
                $this->parentShas($parents),
                $author?->id,
                $message,
                $now->format(CommitHasher::DATE_FORMAT)
            ),
            'message' => $message,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $rows = [];
        foreach ($tree as $path => $blobId) {
            $rows[] = ['commit_id' => $commit->id, 'path' => $path, 'blob_id' => $blobId];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            CommitFile::insert($chunk);
        }

        $this->forgetGraph($repositoryId);

        return $commit;
    }

    /** Recalcula el sha de un commit guardado y comprueba que coincide (integridad). */
    public function verifyCommit(Commit $commit): bool
    {
        $parents = array_values(array_filter([$commit->parent_id, $commit->second_parent_id]));

        $expected = $this->hasher->hash(
            $this->treeMacs($this->tree($commit->id)),
            $this->parentShas($parents),
            $commit->author_id,
            (string) $commit->message,
            $commit->created_at->format(CommitHasher::DATE_FORMAT)
        );

        return hash_equals($expected, (string) $commit->sha);
    }

    /** @param array<string,int> $tree @return array<string,string> ruta => content_mac */
    private function treeMacs(array $tree): array
    {
        $macs = [];
        foreach (array_chunk(array_values(array_unique($tree)), 500) as $ids) {
            $macs += Blob::whereIn('id', $ids)->pluck('content_mac', 'id')->all();
        }

        $result = [];
        foreach ($tree as $path => $blobId) {
            $result[$path] = $macs[$blobId] ?? (string) $blobId;
        }

        return $result;
    }

    /** @param array<int,int> $parents @return array<int,string> */
    private function parentShas(array $parents): array
    {
        if (! $parents) {
            return [];
        }

        $shas = Commit::whereIn('id', $parents)->pluck('sha', 'id');

        return array_map(fn ($id) => (string) ($shas[$id] ?? $id), $parents);
    }

    /**
     * Crea un commit en una rama aplicando cambios.
     *
     * @param  array<int,array{path:string,content?:?string,delete?:bool}>  $changes
     */
    public function commit(Branch $branch, ?User $author, string $message, array $changes): Commit
    {
        $maxFiles = (int) config('codeflow.limits.max_files_per_commit');
        if ($maxFiles > 0 && count($changes) > $maxFiles) {
            abort(422, "Un commit no puede incluir más de {$maxFiles} archivos.");
        }

        return DB::transaction(function () use ($branch, $author, $message, $changes) {
            $locked = Branch::whereKey($branch->id)->lockForUpdate()->firstOrFail();
            $oldTree = $this->tree($locked->head_commit_id);
            $tree = $oldTree;

            foreach ($changes as $change) {
                $path = $this->normalizePath($change['path']);
                if (! empty($change['delete'])) {
                    unset($tree[$path]);
                } else {
                    // 'source': ruta de un archivo temporal (subidas). Se lee aquí, de uno en uno,
                    // para no tener en memoria todos los archivos de una subida grande a la vez.
                    $content = array_key_exists('source', $change)
                        ? (string) file_get_contents($change['source'])
                        : (string) ($change['content'] ?? '');
                    $tree[$path] = $this->objects->put($locked->repository_id, $content, $path)->id;
                    unset($content);
                }
            }

            if ($tree == $oldTree) {
                abort(422, 'No hay cambios que confirmar.');
            }

            $commit = $this->createCommit(
                $locked->repository_id,
                $locked->head_commit_id ? [$locked->head_commit_id] : [],
                $tree,
                $author,
                $message
            );

            $locked->update(['head_commit_id' => $commit->id]);
            $branch->head_commit_id = $commit->id;

            return $commit;
        });
    }

    /**
     * Diferencias entre dos commits, con recuento real de líneas (Myers).
     *
     * Cada elemento: path, status (added|deleted|modified), additions, deletions,
     * binary (sin diff de líneas) y too_large (más de max_diff_lines líneas:
     * recuento aproximado y sin hunks). Con $withHunks se añade "hunks"
     * (ver App\Vcs\Diff\HunkBuilder para su forma exacta).
     *
     * @return array<int,array{path:string,status:string,additions:int,deletions:int,binary:bool,too_large:bool,hunks?:array}>
     */
    public function diff(?int $baseCommitId, ?int $headCommitId, bool $withHunks = false, ?array $hunkPaths = null): array
    {
        // El resumen (rutas y +/−) se guarda en caché: los commits son inmutables, así que la
        // pareja (base, head) siempre da el mismo resultado. Los hunks (con el código descifrado)
        // nunca se guardan en caché.
        if (! $withHunks) {
            return Cache::rememberForever(
                'vcs:diff:'.($baseCommitId ?? 0).':'.($headCommitId ?? 0),
                fn () => $this->computeDiff($baseCommitId, $headCommitId, false, null),
            );
        }

        return $this->computeDiff($baseCommitId, $headCommitId, true, $hunkPaths);
    }

    /** Diff de un solo archivo con sus hunks (para cargarlo bajo demanda). */
    public function fileDiff(?int $baseCommitId, ?int $headCommitId, string $path): ?array
    {
        return $this->computeDiff($baseCommitId, $headCommitId, true, [$path], $path)[0] ?? null;
    }

    /**
     * @param  array<int,string>|null  $hunkPaths  Si se indica, solo se calculan los hunks de esas rutas.
     * @param  string|null  $onlyPath  Limita el diff a una ruta.
     */
    private function computeDiff(?int $baseCommitId, ?int $headCommitId, bool $withHunks, ?array $hunkPaths, ?string $onlyPath = null): array
    {
        $old = $this->tree($baseCommitId);
        $new = $this->tree($headCommitId);

        $paths = $onlyPath !== null ? [$onlyPath] : array_unique(array_merge(array_keys($old), array_keys($new)));
        sort($paths);

        $changed = [];
        foreach ($paths as $path) {
            $o = $old[$path] ?? null;
            $n = $new[$path] ?? null;
            if ($o !== $n) {
                $changed[$path] = [$o, $n];
            }
        }

        $ids = collect($changed)->flatten()->filter()->unique()->values()->all();
        $blobs = $ids ? Blob::whereIn('id', $ids)->get()->keyBy('id') : collect();

        $maxLines = (int) config('codeflow.limits.max_diff_lines');
        $myers = new MyersDiff;
        $hunkBuilder = new HunkBuilder(3);
        $wantHunks = $hunkPaths !== null ? array_flip($hunkPaths) : null;

        $result = [];
        foreach ($changed as $path => [$o, $n]) {
            $oldBlob = $o ? $blobs[$o] ?? null : null;
            $newBlob = $n ? $blobs[$n] ?? null : null;
            $hunksHere = $withHunks && ($wantHunks === null || isset($wantHunks[(string) $path]));

            $entry = [
                'path' => (string) $path,
                'status' => $o === null ? 'added' : ($n === null ? 'deleted' : 'modified'),
                'additions' => 0,
                'deletions' => 0,
                'binary' => (bool) ($oldBlob?->is_binary || $newBlob?->is_binary),
                'too_large' => false,
            ];
            $hunks = [];

            if (! $entry['binary']) {
                $oldLines = $oldBlob ? $this->lines($oldBlob->content) : [];
                $newLines = $newBlob ? $this->lines($newBlob->content) : [];

                if ($maxLines > 0 && (count($oldLines) > $maxLines || count($newLines) > $maxLines)) {
                    $entry['too_large'] = true;
                    [$entry['additions'], $entry['deletions']] = $this->approximateCounts($oldLines, $newLines);
                } else {
                    $script = $myers->diff($oldLines, $newLines);
                    foreach ($script as $op) {
                        $entry['additions'] += $op['type'] === MyersDiff::INSERT ? 1 : 0;
                        $entry['deletions'] += $op['type'] === MyersDiff::DELETE ? 1 : 0;
                    }
                    if ($hunksHere) {
                        $hunks = $hunkBuilder->build($script, $oldLines, $newLines);
                    }
                }
            }

            if ($hunksHere) {
                $entry['hunks'] = $hunks;
            }

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Lo que aporta $head respecto a $baseHead (como `git diff base...head`):
     * diff(mergeBase(base, head), head).
     */
    public function compare(int $repositoryId, ?int $baseHead, ?int $head, bool $withHunks = false): array
    {
        if (! $head) {
            return [];
        }

        $base = $baseHead ? $this->mergeBase($this->graph($repositoryId), $baseHead, $head) : null;

        return $this->diff($base, $head, $withHunks);
    }

    /** Divide en líneas; un salto de línea final no crea una línea vacía extra. */
    private function lines(string $content): array
    {
        if ($content === '') {
            return [];
        }

        $lines = preg_split('/\R/', $content);
        if (end($lines) === '') {
            array_pop($lines);
        }

        return $lines;
    }

    /**
     * Recuento aproximado (multiconjunto de líneas) para archivos demasiado
     * grandes para Myers.
     *
     * @return array{0:int,1:int} [additions, deletions]
     */
    private function approximateCounts(array $oldLines, array $newLines): array
    {
        $oldCount = array_count_values(array_map('strval', $oldLines));
        $newCount = array_count_values(array_map('strval', $newLines));
        $additions = $deletions = 0;

        foreach ($newCount as $line => $count) {
            $additions += max(0, $count - ($oldCount[$line] ?? 0));
        }
        foreach ($oldCount as $line => $count) {
            $deletions += max(0, $count - ($newCount[$line] ?? 0));
        }

        return [$additions, $deletions];
    }

    /**
     * Calcula (sin escribir nada) el resultado de fusionar sourceHead en targetHead.
     *
     * @return array{tree:array<string,int>,conflicts:array<int,string>}
     */
    public function planMerge(Collection $graph, ?int $targetHead, int $sourceHead): array
    {
        $ancestorsOfSource = $this->ancestors($graph, $sourceHead);

        // Fast-forward: el destino está contenido en el origen (o está vacío)
        if (! $targetHead || isset($ancestorsOfSource[$targetHead])) {
            return ['tree' => $this->tree($sourceHead), 'conflicts' => []];
        }

        $baseId = $this->mergeBase($graph, $targetHead, $sourceHead);
        $base = $this->tree($baseId);
        $ours = $this->tree($targetHead);
        $theirs = $this->tree($sourceHead);

        $paths = array_unique(array_merge(array_keys($base), array_keys($ours), array_keys($theirs)));
        $merged = [];
        $conflicts = [];

        foreach ($paths as $path) {
            $b = $base[$path] ?? null;
            $o = $ours[$path] ?? null;
            $t = $theirs[$path] ?? null;

            if ($o === $t) {
                $result = $o;
            } elseif ($b === $o) {
                $result = $t;
            } elseif ($b === $t) {
                $result = $o;
            } else {
                $conflicts[] = $path;

                continue;
            }

            if ($result !== null) {
                $merged[$path] = $result;
            }
        }

        sort($conflicts);

        return ['tree' => $merged, 'conflicts' => $conflicts];
    }

    /** Fusiona un Pull Request creando un commit de fusión en la rama destino. */
    public function merge(PullRequest $pr, User $user): Commit
    {
        return DB::transaction(function () use ($pr, $user) {
            $target = Branch::whereKey($pr->target_branch_id)->lockForUpdate()->first();
            $source = Branch::find($pr->source_branch_id);

            if (! $target || ! $source) {
                abort(422, 'Alguna de las ramas del Pull Request ya no existe.');
            }

            $s = $source->head_commit_id;
            $t = $target->head_commit_id;

            if (! $s) {
                abort(422, 'La rama origen no tiene commits.');
            }

            $graph = $this->graph($pr->repository_id);

            if ($t && isset($this->ancestors($graph, $t)[$s])) {
                abort(422, 'Los cambios ya están incluidos en la rama destino.');
            }

            $plan = $this->planMerge($graph, $t, $s);

            if ($plan['conflicts']) {
                abort(response()->json([
                    'message' => 'Hay conflictos de fusión que deben resolverse en la rama origen.',
                    'conflicts' => $plan['conflicts'],
                ], 409));
            }

            $message = "Merge pull request #{$pr->number} from {$source->name}\n\n{$pr->title}";
            $commit = $this->createCommit(
                $pr->repository_id,
                array_values(array_filter([$t, $s])),
                $plan['tree'],
                $user,
                $message
            );

            $target->update(['head_commit_id' => $commit->id]);

            return $commit;
        });
    }
}
