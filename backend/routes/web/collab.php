<?php

use App\Http\Controllers\Web\Collab\IssueController;
use App\Http\Controllers\Web\Collab\ProjectCollabController;
use App\Http\Controllers\Web\Collab\PullRequestAnalysisController;
use App\Http\Controllers\Web\Collab\PullRequestController;
use Illuminate\Support\Facades\Route;

// Issues y Pull Requests ({number} = número dentro del repositorio: #1, #2...)

// Lectura: los repos públicos se ven sin sesión (la Policy decide; privados → 404)
Route::get('repositories/{repository}/issues', [IssueController::class, 'index'])->name('issues.index');
Route::get('repositories/{repository}/pulls', [PullRequestController::class, 'index'])->name('pulls.index');

Route::middleware(['auth', 'verified'])->group(function () {
    // Issues y pull requests de todo el proyecto (agregan las de sus repositorios)
    Route::get('projects/{project}/issues', [ProjectCollabController::class, 'issues'])->name('projects.issues');
    Route::get('projects/{project}/pulls', [ProjectCollabController::class, 'pulls'])->name('projects.pulls');

    Route::get('repositories/{repository}/issues/create', [IssueController::class, 'create'])->name('issues.create');
    Route::post('repositories/{repository}/issues', [IssueController::class, 'store'])->name('issues.store');
    Route::put('repositories/{repository}/issues/{number}', [IssueController::class, 'update'])->whereNumber('number')->name('issues.update');
    Route::delete('repositories/{repository}/issues/{number}', [IssueController::class, 'destroy'])->whereNumber('number')->name('issues.destroy');
    Route::post('repositories/{repository}/issues/{number}/comments', [IssueController::class, 'comment'])->whereNumber('number')->name('issues.comment');

    Route::get('repositories/{repository}/pulls/create', [PullRequestController::class, 'create'])->name('pulls.create');
    Route::post('repositories/{repository}/pulls', [PullRequestController::class, 'store'])->name('pulls.store');
    Route::post('repositories/{repository}/pulls/{number}/comments', [PullRequestController::class, 'comment'])->whereNumber('number')->name('pulls.comment');
    Route::put('repositories/{repository}/pulls/{number}', [PullRequestController::class, 'update'])->whereNumber('number')->name('pulls.update');
    Route::post('repositories/{repository}/pulls/{number}/reviews', [PullRequestController::class, 'review'])->whereNumber('number')->name('pulls.review');
    Route::post('repositories/{repository}/pulls/{number}/merge', [PullRequestController::class, 'merge'])->whereNumber('number')->name('pulls.merge');
    Route::get('repositories/{repository}/pulls/{number}/conflicts', [PullRequestController::class, 'conflicts'])->whereNumber('number')->name('pulls.conflicts');
    Route::post('repositories/{repository}/pulls/{number}/conflicts', [PullRequestController::class, 'resolve'])->whereNumber('number')->name('pulls.resolve');
    Route::post('repositories/{repository}/pulls/{number}/close', [PullRequestController::class, 'close'])->whereNumber('number')->name('pulls.close');
    Route::post('repositories/{repository}/pulls/{number}/reopen', [PullRequestController::class, 'reopen'])->whereNumber('number')->name('pulls.reopen');
    // Revisión con IA (opcional): se lanza en segundo plano
    Route::post('repositories/{repository}/pulls/{number}/ai', [PullRequestAnalysisController::class, 'store'])->whereNumber('number')->middleware('throttle:6,1')->name('pulls.ai.store');
});

Route::get('repositories/{repository}/issues/{number}', [IssueController::class, 'show'])->whereNumber('number')->name('issues.show');
Route::get('repositories/{repository}/pulls/{number}', [PullRequestController::class, 'show'])->whereNumber('number')->name('pulls.show');
Route::get('repositories/{repository}/pulls/{number}/diff', [PullRequestController::class, 'diff'])->whereNumber('number')->name('pulls.diff');
Route::get('repositories/{repository}/pulls/{number}/ai', [PullRequestAnalysisController::class, 'show'])->whereNumber('number')->name('pulls.ai.show');
