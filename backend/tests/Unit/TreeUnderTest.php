<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Services\VcsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Web\Account\CreatesProjects;
use Tests\TestCase;

/** R06: el contenido de una carpeta se filtra en SQL sin confundir comodines de LIKE. */
class TreeUnderTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    public function test_tree_under_filters_by_folder_and_escapes_like_wildcards(): void
    {
        $this->setUpTeam();
        $repo = $this->makeRepository($this->project, $this->owner, 'web');
        $branch = Branch::where('repository_id', $repo->id)->where('name', 'main')->firstOrFail();
        $commit = app(VcsService::class)->commit($branch, $this->owner, 'Estructura', [
            ['path' => 'mi_app/a.txt', 'content' => "a\n"],
            ['path' => 'mi_app/sub/b.txt', 'content' => "b\n"],
            ['path' => 'miXapp/c.txt', 'content' => "c\n"],        // coincidiría con «mi_app» si no se escapa el _
            ['path' => 'mi_app.txt', 'content' => "d\n"],
            ['path' => '100%/e.txt', 'content' => "e\n"],
        ]);

        $vcs = new VcsService; // sin memoria previa: fuerza la consulta SQL
        $this->assertSame(['mi_app/a.txt', 'mi_app/sub/b.txt'], array_keys($vcs->treeUnder($commit->id, 'mi_app')));
        $this->assertSame(['100%/e.txt'], array_keys((new VcsService)->treeUnder($commit->id, '100%')));
        $this->assertCount(6, (new VcsService)->treeUnder($commit->id, '')); // README.md inicial + 5
    }
}
