<?php

namespace App\Services;

use App\Models\Blob;
use App\Models\Branch;
use App\Models\Commit;
use App\Models\CommitFile;
use App\Models\PullRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Capa simplificada de control de versiones sobre la base de datos.
 *
 * - Cada commit guarda un snapshot completo del árbol (path => blob).
 * - Los contenidos se deduplican por hash dentro del repositorio.
 * - Una rama es un puntero a un commit (head_commit_id).
 * - La fusión es a nivel de archivo (3-way merge); si ambos lados cambian
 *   el mismo archivo de forma distinta, se devuelve un conflicto (409).
 */
class VcsService
{
    /** @return array<string,int> path => blob_id */
    public function tree(?int $commitId): array
    {
        if (! $commitId) {
            return [];
        }

        return CommitFile::where('commit_id', $commitId)->pluck('blob_id', 'path')->all();
    }

    /** Grafo mínimo de commits de un repositorio (id, parent_id, second_parent_id). */
    public function graph(int $repositoryId): Collection
    {
        return Commit::where('repository_id', $repositoryId)
            ->get(['id', 'parent_id', 'second_parent_id'])
            ->keyBy('id');
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

    public function blob(int $repositoryId, string $content): Blob
    {
        return Blob::firstOrCreate(
            ['repository_id' => $repositoryId, 'hash' => sha1($content)],
            ['content' => $content]
        );
    }

    /** @param array<int,int> $parents @param array<string,int> $tree */
    public function createCommit(int $repositoryId, array $parents, array $tree, ?User $author, string $message): Commit
    {
        $commit = Commit::create([
            'repository_id' => $repositoryId,
            'parent_id' => $parents[0] ?? null,
            'second_parent_id' => $parents[1] ?? null,
            'author_id' => $author?->id,
            'sha' => sha1(json_encode([$tree, $parents, $message]).Str::uuid()),
            'message' => $message,
        ]);

        $rows = [];
        foreach ($tree as $path => $blobId) {
            $rows[] = ['commit_id' => $commit->id, 'path' => $path, 'blob_id' => $blobId];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            CommitFile::insert($chunk);
        }

        return $commit;
    }

    /**
     * Crea un commit en una rama aplicando cambios.
     *
     * @param array<int,array{path:string,content?:?string,delete?:bool}> $changes
     */
    public function commit(Branch $branch, ?User $author, string $message, array $changes): Commit
    {
        return DB::transaction(function () use ($branch, $author, $message, $changes) {
            $locked = Branch::whereKey($branch->id)->lockForUpdate()->firstOrFail();
            $oldTree = $this->tree($locked->head_commit_id);
            $tree = $oldTree;

            foreach ($changes as $change) {
                $path = $this->normalizePath($change['path']);
                if (! empty($change['delete'])) {
                    unset($tree[$path]);
                } else {
                    $tree[$path] = $this->blob($locked->repository_id, (string) ($change['content'] ?? ''))->id;
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
     * Diferencias entre dos commits (estado y recuento aproximado de líneas).
     *
     * @return array<int,array{path:string,status:string,additions:int,deletions:int}>
     */
    public function diff(?int $baseCommitId, ?int $headCommitId): array
    {
        $old = $this->tree($baseCommitId);
        $new = $this->tree($headCommitId);

        $paths = array_unique(array_merge(array_keys($old), array_keys($new)));
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
        $contents = $ids ? Blob::whereIn('id', $ids)->pluck('content', 'id') : collect();

        $result = [];
        foreach ($changed as $path => [$o, $n]) {
            $oldLines = $o ? $this->lines($contents[$o] ?? '') : [];
            $newLines = $n ? $this->lines($contents[$n] ?? '') : [];
            $result[] = [
                'path' => $path,
                'status' => $o === null ? 'added' : ($n === null ? 'deleted' : 'modified'),
                'additions' => count(array_diff($newLines, $oldLines)),
                'deletions' => count(array_diff($oldLines, $newLines)),
            ];
        }

        return $result;
    }

    private function lines(string $content): array
    {
        return $content === '' ? [] : preg_split('/\R/', $content);
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
