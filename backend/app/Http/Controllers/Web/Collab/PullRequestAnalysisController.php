<?php

namespace App\Http\Controllers\Web\Collab;

use App\Ai\AiUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Services\PullRequestAnalysisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Revisión de un pull request con IA (opcional): lanzarla y consultar su estado. */
class PullRequestAnalysisController extends Controller
{
    public function __construct(private readonly PullRequestAnalysisService $analyses) {}

    /** POST: lanza el análisis en segundo plano. */
    public function store(Request $request, Repository $repository, int $number): RedirectResponse
    {
        abort_unless(PullRequestAnalysisService::enabled(), 404);
        $pr = $this->find($repository, $number);
        Gate::authorize('analyze', $pr);

        $back = redirect()->to(route('pulls.show', [$repository, $number]).'#analisis-ia');

        try {
            $this->analyses->request($pr, $request->user());
        } catch (AiUnavailableException $e) {
            return $back->with('error', $e->getMessage());
        }

        return $back->with('status', 'Análisis con IA en marcha. Los resultados aparecerán aquí en cuanto terminen.');
    }

    /** GET: fragmento HTML con el estado y los resultados (la página lo consulta mientras analiza). */
    public function show(Repository $repository, int $number): View
    {
        abort_unless(PullRequestAnalysisService::enabled(), 404);
        Gate::authorize('view', $repository);
        $pr = $this->find($repository, $number);

        return view('pulls._ai', ['repository' => $repository, 'pr' => $pr, 'analysis' => $pr->latestAiAnalysis()->with(['findings', 'requester:id,name'])->first()]);
    }

    private function find(Repository $repository, int $number): PullRequest
    {
        return PullRequest::where('repository_id', $repository->id)->where('number', $number)->firstOrFail();
    }
}
