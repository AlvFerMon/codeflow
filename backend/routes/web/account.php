<?php

use App\Http\Controllers\Web\Account\ActivityController;
use App\Http\Controllers\Web\Account\ExploreController;
use App\Http\Controllers\Web\Account\HomeController;
use App\Http\Controllers\Web\Account\MemberController;
use App\Http\Controllers\Web\Account\NotificationController;
use App\Http\Controllers\Web\Account\ProfileController;
use App\Http\Controllers\Web\Account\ProjectController;
use Illuminate\Support\Facades\Route;

// Portada, exploración, perfil, notificaciones, proyectos y miembros
Route::get('/', HomeController::class)->name('home');
Route::get('explore', ExploreController::class)->middleware('throttle:explore')->name('explore');

// El perfil también está disponible sin verificar (p. ej. para corregir un email mal escrito)
Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:10,1')->name('profile.password');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('activity', [ActivityController::class, 'global'])->name('activity.index');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::get('projects/{project}/activity', ActivityController::class)->name('projects.activity');

    Route::get('projects/{project}/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('projects/{project}/members', [MemberController::class, 'store'])->name('members.store');
    Route::put('projects/{project}/members/{user}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('projects/{project}/members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');
});
