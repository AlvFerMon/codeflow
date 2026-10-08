<?php

use App\Http\Controllers\Web\PlaceholderController as P;
use Illuminate\Support\Facades\Route;

// Portada, exploración, perfil, notificaciones, proyectos y miembros
Route::get('/', P::class)->defaults('title', 'Inicio')->name('home');
Route::get('explore', P::class)->defaults('title', 'Explorar repositorios públicos')->name('explore');

Route::middleware('auth')->group(function () {
    Route::get('profile', P::class)->defaults('title', 'Mi perfil')->name('profile.edit');
    Route::put('profile', P::class)->name('profile.update');
    Route::put('profile/password', P::class)->name('profile.password');

    Route::get('notifications', P::class)->defaults('title', 'Notificaciones')->name('notifications.index');
    Route::post('notifications/read-all', P::class)->name('notifications.readAll');
    Route::post('notifications/{id}/read', P::class)->name('notifications.read');

    Route::get('projects', P::class)->defaults('title', 'Mis proyectos')->name('projects.index');
    Route::get('projects/create', P::class)->defaults('title', 'Nuevo proyecto')->name('projects.create');
    Route::post('projects', P::class)->name('projects.store');
    Route::get('projects/{project}', P::class)->defaults('title', 'Proyecto')->name('projects.show');
    Route::get('projects/{project}/edit', P::class)->defaults('title', 'Editar proyecto')->name('projects.edit');
    Route::put('projects/{project}', P::class)->name('projects.update');
    Route::delete('projects/{project}', P::class)->name('projects.destroy');
    Route::get('projects/{project}/activity', P::class)->defaults('title', 'Actividad')->name('projects.activity');

    Route::get('projects/{project}/members', P::class)->defaults('title', 'Miembros')->name('members.index');
    Route::post('projects/{project}/members', P::class)->name('members.store');
    Route::put('projects/{project}/members/{user}', P::class)->name('members.update');
    Route::delete('projects/{project}/members/{user}', P::class)->name('members.destroy');
});
