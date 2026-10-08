<?php

use App\Http\Controllers\Web\PlaceholderController as P;
use Illuminate\Support\Facades\Route;

// Repositorios, código, ramas y commits

// Lectura (los repos públicos se ven sin sesión)
Route::get('repositories/{repository}', P::class)->defaults('title', 'Código')->name('repositories.show');
Route::get('repositories/{repository}/blob', P::class)->defaults('title', 'Archivo')->name('repositories.blob');
Route::get('repositories/{repository}/raw', P::class)->defaults('title', 'Descarga')->name('repositories.raw');
Route::get('repositories/{repository}/branches', P::class)->defaults('title', 'Ramas')->defaults('tab', 'branches')->name('branches.index');
Route::get('repositories/{repository}/commits', P::class)->defaults('title', 'Commits')->defaults('tab', 'commits')->name('commits.index');
Route::get('repositories/{repository}/commits/{sha}', P::class)->defaults('title', 'Commit')->defaults('tab', 'commits')->name('commits.show');

Route::middleware('auth')->group(function () {
    Route::get('projects/{project}/repositories/create', P::class)->defaults('title', 'Nuevo repositorio')->name('repositories.create');
    Route::post('projects/{project}/repositories', P::class)->name('repositories.store');
    Route::get('repositories/{repository}/settings', P::class)->defaults('title', 'Configuración')->defaults('tab', 'settings')->name('repositories.settings');
    Route::put('repositories/{repository}', P::class)->name('repositories.update');
    Route::delete('repositories/{repository}', P::class)->name('repositories.destroy');

    Route::get('repositories/{repository}/files/new', P::class)->defaults('title', 'Subir archivos')->name('files.create');
    Route::get('repositories/{repository}/files/edit', P::class)->defaults('title', 'Editar archivo')->name('files.edit');
    Route::post('repositories/{repository}/files', P::class)->name('files.store');
    Route::delete('repositories/{repository}/files', P::class)->name('files.destroy');

    Route::post('repositories/{repository}/branches', P::class)->name('branches.store');
    Route::delete('branches/{branch}', P::class)->name('branches.destroy');
});
