<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\Web\Account\ActivityController;
use App\Http\Controllers\Web\Account\ExploreController;
use App\Http\Controllers\Web\Account\HomeController;
use App\Http\Controllers\Web\Account\InvitationController;
use App\Http\Controllers\Web\Account\MemberController;
use App\Http\Controllers\Web\Account\NotificationController;
use App\Http\Controllers\Web\Account\ProfileController;
use App\Http\Controllers\Web\Account\ProjectController;
use Illuminate\Support\Facades\Route;

// Portada, exploración, perfil, notificaciones, proyectos y miembros
Route::get('/', HomeController::class)->name('home');
Route::get('explore', ExploreController::class)->middleware('throttle:explore')->name('explore');
Route::view('descargas', 'downloads.index')->name('downloads');

// Páginas legales (públicas: también para invitados, móviles y cuentas sin verificar)
Route::get('legal/{page}', LegalController::class)->where('page', 'privacidad|cookies|terminos|aviso-legal')->name('legal');

// Enlace del correo de invitación (público: lleva a crear la cuenta o, con sesión, al proyecto)
Route::get('invitations/{token}', InvitationController::class)->middleware('throttle:20,1')->name('invitations.show');

// El perfil también está disponible sin verificar (p. ej. para corregir un email mal escrito)
Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:10,1')->name('profile.password');
    Route::patch('profile/preferences', [PreferencesController::class, 'update'])->name('profile.preferences');
    Route::get('profile/devices', [DeviceController::class, 'index'])->name('profile.devices');
    Route::delete('profile/devices/others', [DeviceController::class, 'destroyOthers'])->name('profile.devices.others');
    Route::delete('profile/devices/apps/{id}', [DeviceController::class, 'destroyToken'])->whereNumber('id')->name('profile.devices.token');
    Route::get('profile/export', [AccountController::class, 'export'])->middleware('throttle:5,1')->name('profile.export');
    Route::delete('profile', [AccountController::class, 'destroy'])->middleware('throttle:5,1')->name('profile.destroy');
    Route::delete('profile/devices/web/{id}', [DeviceController::class, 'destroySession'])->where('id', '[A-Za-z0-9]{20,100}')->name('profile.devices.session');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('activity', [ActivityController::class, 'global'])->name('activity.index');

    // Tutorial de bienvenida (mismo controlador que la API)
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [OnboardingController::class, 'show'])->name('show');
        Route::post('tour/progress', [OnboardingController::class, 'progress'])->name('progress');
        Route::post('tour/complete', [OnboardingController::class, 'complete'])->name('complete');
        Route::post('tour/skip', [OnboardingController::class, 'skip'])->name('skip');
        Route::post('restart', [OnboardingController::class, 'restart'])->name('restart');
        Route::post('checklist/hide', [OnboardingController::class, 'hideChecklist'])->name('checklist.hide');
        Route::post('checklist/show', [OnboardingController::class, 'showChecklist'])->name('checklist.show');
        Route::post('sample-project', [OnboardingController::class, 'sampleProject'])->middleware('throttle:5,1')->name('sample');
    });

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->whereUuid('id')->name('notifications.read');

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
    Route::delete('projects/{project}/invitations/{invitation}', [MemberController::class, 'cancelInvitation'])->name('invitations.destroy');

    // Papelera
    Route::get('papelera', [TrashController::class, 'index'])->name('trash.index');
    Route::post('papelera/proyectos/{id}/restaurar', [TrashController::class, 'restoreProject'])->whereNumber('id')->name('trash.projects.restore');
    Route::delete('papelera/proyectos/{id}', [TrashController::class, 'destroyProject'])->whereNumber('id')->name('trash.projects.destroy');
    Route::post('papelera/repositorios/{id}/restaurar', [TrashController::class, 'restoreRepository'])->whereNumber('id')->name('trash.repositories.restore');
    Route::delete('papelera/repositorios/{id}', [TrashController::class, 'destroyRepository'])->whereNumber('id')->name('trash.repositories.destroy');
});
