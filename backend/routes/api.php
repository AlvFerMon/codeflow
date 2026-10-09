<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CommitController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\RepositoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de CodeFlow  (prefijo /api añadido por Laravel)
|--------------------------------------------------------------------------
| - Lecturas públicas: autenticación opcional. Sin token se actúa como invitado
|   y las policies deciden (repos públicos sí, privados 404).
| - Escrituras y datos personales: auth:sanctum (401 sin token) y email verificado
|   (403 con code=email_not_verified). Sin verificar se actúa como invitado en las lecturas.
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
});

// Lecturas públicas (repos públicos sin login; privados solo para miembros)
Route::middleware(['optional.sanctum', 'throttle:120,1'])->group(function () {
    Route::get('explore/repositories', [RepositoryController::class, 'explore']);
    Route::get('projects/{project}/repositories', [RepositoryController::class, 'index']);

    Route::get('repositories/{repository}', [RepositoryController::class, 'show']);
    Route::get('repositories/{repository}/branches', [BranchController::class, 'index']);

    Route::get('branches/{branch}/files', [FileController::class, 'index']);
    Route::get('branches/{branch}/file', [FileController::class, 'show']);

    Route::get('repositories/{repository}/commits', [CommitController::class, 'index']);
    Route::get('branches/{branch}/commits', [CommitController::class, 'branchHistory']);
    Route::get('commits/{commit}', [CommitController::class, 'show']);

    Route::get('repositories/{repository}/issues', [IssueController::class, 'index']);
    Route::get('issues/{issue}', [IssueController::class, 'show']);
    Route::get('issues/{issue}/comments', [IssueController::class, 'comments']);

    Route::get('repositories/{repository}/pull-requests', [PullRequestController::class, 'index']);
    Route::get('pull-requests/{pullRequest}', [PullRequestController::class, 'show']);
    Route::get('pull-requests/{pullRequest}/files', [PullRequestController::class, 'filesEndpoint']);
    Route::get('pull-requests/{pullRequest}/comments', [PullRequestController::class, 'comments']);
});

// Sesión y perfil: disponibles aunque el email no esté verificado
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/email/verify', [AuthController::class, 'verifyEmail'])->middleware('throttle:verification-code');
    Route::post('auth/email/verification-notification', [AuthController::class, 'resendVerification'])->middleware('throttle:verification-send');
    Route::get('me', [AuthController::class, 'me']);
    Route::put('me', [AuthController::class, 'updateProfile']);
    Route::put('me/password', [AuthController::class, 'changePassword'])->middleware('throttle:10,1');
});

// Todo lo demás exige email verificado (403 con code=email_not_verified)
Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    // Notificaciones
    Route::get('me/activity', [ActivityController::class, 'mine']);
    Route::get('me/notifications', [NotificationController::class, 'index']);
    Route::get('me/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('me/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('me/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Proyectos y miembros
    Route::apiResource('projects', ProjectController::class)->names('api.projects');
    Route::get('projects/{project}/dashboard', [ProjectController::class, 'dashboard']);
    Route::get('projects/{project}/activity', [ActivityController::class, 'index']);
    Route::get('projects/{project}/members', [MemberController::class, 'index']);
    Route::post('projects/{project}/members', [MemberController::class, 'store']);
    Route::put('projects/{project}/members/{user}', [MemberController::class, 'update']);
    Route::delete('projects/{project}/members/{user}', [MemberController::class, 'destroy']);

    // Repositorios
    Route::post('projects/{project}/repositories', [RepositoryController::class, 'store']);
    Route::put('repositories/{repository}', [RepositoryController::class, 'update']);
    Route::delete('repositories/{repository}', [RepositoryController::class, 'destroy']);

    // Ramas y commits
    Route::post('repositories/{repository}/branches', [BranchController::class, 'store']);
    Route::delete('branches/{branch}', [BranchController::class, 'destroy']);
    Route::post('branches/{branch}/commits', [CommitController::class, 'store']);

    // Issues
    Route::post('repositories/{repository}/issues', [IssueController::class, 'store']);
    Route::put('issues/{issue}', [IssueController::class, 'update']);
    Route::delete('issues/{issue}', [IssueController::class, 'destroy']);
    Route::post('issues/{issue}/comments', [IssueController::class, 'storeComment']);

    // Pull Requests
    Route::post('repositories/{repository}/pull-requests', [PullRequestController::class, 'store']);
    Route::put('pull-requests/{pullRequest}', [PullRequestController::class, 'update']);
    Route::post('pull-requests/{pullRequest}/comments', [PullRequestController::class, 'storeComment']);
    Route::post('pull-requests/{pullRequest}/reviews', [PullRequestController::class, 'storeReview']);
    Route::post('pull-requests/{pullRequest}/merge', [PullRequestController::class, 'merge']);
    Route::post('pull-requests/{pullRequest}/close', [PullRequestController::class, 'close']);
    Route::post('pull-requests/{pullRequest}/reopen', [PullRequestController::class, 'reopen']);
});
