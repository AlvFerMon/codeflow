<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CommitController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\RepositoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de CodeFlow  (prefijo /api añadido por Laravel)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
});

Route::middleware('auth:sanctum')->group(function () {
    // Sesión y perfil
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::put('me', [AuthController::class, 'updateProfile']);

    // Proyectos y miembros
    Route::apiResource('projects', ProjectController::class);
    Route::get('projects/{project}/dashboard', [ProjectController::class, 'dashboard']);
    Route::get('projects/{project}/activity', [ActivityController::class, 'index']);
    Route::get('projects/{project}/members', [MemberController::class, 'index']);
    Route::post('projects/{project}/members', [MemberController::class, 'store']);
    Route::put('projects/{project}/members/{user}', [MemberController::class, 'update']);
    Route::delete('projects/{project}/members/{user}', [MemberController::class, 'destroy']);

    // Repositorios
    Route::get('projects/{project}/repositories', [RepositoryController::class, 'index']);
    Route::post('projects/{project}/repositories', [RepositoryController::class, 'store']);
    Route::get('repositories/{repository}', [RepositoryController::class, 'show']);
    Route::put('repositories/{repository}', [RepositoryController::class, 'update']);
    Route::delete('repositories/{repository}', [RepositoryController::class, 'destroy']);

    // Ramas
    Route::get('repositories/{repository}/branches', [BranchController::class, 'index']);
    Route::post('repositories/{repository}/branches', [BranchController::class, 'store']);
    Route::delete('branches/{branch}', [BranchController::class, 'destroy']);

    // Archivos de una rama
    Route::get('branches/{branch}/files', [FileController::class, 'index']);
    Route::get('branches/{branch}/file', [FileController::class, 'show']);

    // Commits
    Route::get('repositories/{repository}/commits', [CommitController::class, 'index']);
    Route::get('branches/{branch}/commits', [CommitController::class, 'branchHistory']);
    Route::post('branches/{branch}/commits', [CommitController::class, 'store']);
    Route::get('commits/{commit}', [CommitController::class, 'show']);

    // Issues
    Route::get('repositories/{repository}/issues', [IssueController::class, 'index']);
    Route::post('repositories/{repository}/issues', [IssueController::class, 'store']);
    Route::get('issues/{issue}', [IssueController::class, 'show']);
    Route::put('issues/{issue}', [IssueController::class, 'update']);
    Route::post('issues/{issue}/comments', [IssueController::class, 'storeComment']);

    // Pull Requests
    Route::get('repositories/{repository}/pull-requests', [PullRequestController::class, 'index']);
    Route::post('repositories/{repository}/pull-requests', [PullRequestController::class, 'store']);
    Route::get('pull-requests/{pullRequest}', [PullRequestController::class, 'show']);
    Route::put('pull-requests/{pullRequest}', [PullRequestController::class, 'update']);
    Route::get('pull-requests/{pullRequest}/files', [PullRequestController::class, 'filesEndpoint']);
    Route::post('pull-requests/{pullRequest}/comments', [PullRequestController::class, 'storeComment']);
    Route::post('pull-requests/{pullRequest}/reviews', [PullRequestController::class, 'storeReview']);
    Route::post('pull-requests/{pullRequest}/merge', [PullRequestController::class, 'merge']);
    Route::post('pull-requests/{pullRequest}/close', [PullRequestController::class, 'close']);
});
