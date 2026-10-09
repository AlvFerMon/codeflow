<?php

use App\Http\Controllers\Web\Code\BranchController;
use App\Http\Controllers\Web\Code\CommitController;
use App\Http\Controllers\Web\Code\FileController;
use App\Http\Controllers\Web\Code\RepositoryController;
use Illuminate\Support\Facades\Route;

// Repositorios, código, ramas y commits

// Lectura (los repos públicos se ven sin sesión)
Route::get('repositories/{repository}', [RepositoryController::class, 'show'])->name('repositories.show');
Route::get('repositories/{repository}/blob', [RepositoryController::class, 'blob'])->name('repositories.blob');
Route::get('repositories/{repository}/raw', [RepositoryController::class, 'raw'])->name('repositories.raw');
Route::get('repositories/{repository}/branches', [BranchController::class, 'index'])->name('branches.index');
Route::get('repositories/{repository}/commits', [CommitController::class, 'index'])->name('commits.index');
Route::get('repositories/{repository}/commits/{sha}', [CommitController::class, 'show'])->name('commits.show');
Route::get('repositories/{repository}/commits/{sha}/diff', [CommitController::class, 'diff'])->name('commits.diff');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('projects/{project}/repositories/create', [RepositoryController::class, 'create'])->name('repositories.create');
    Route::post('projects/{project}/repositories', [RepositoryController::class, 'store'])->name('repositories.store');
    Route::get('repositories/{repository}/settings', [RepositoryController::class, 'settings'])->name('repositories.settings');
    Route::put('repositories/{repository}', [RepositoryController::class, 'update'])->name('repositories.update');
    Route::delete('repositories/{repository}', [RepositoryController::class, 'destroy'])->name('repositories.destroy');

    Route::get('repositories/{repository}/files/new', [FileController::class, 'create'])->name('files.create');
    Route::get('repositories/{repository}/files/edit', [FileController::class, 'edit'])->name('files.edit');
    Route::post('repositories/{repository}/files', [FileController::class, 'store'])->name('files.store');
    Route::delete('repositories/{repository}/files', [FileController::class, 'destroy'])->name('files.destroy');

    Route::post('repositories/{repository}/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
});
