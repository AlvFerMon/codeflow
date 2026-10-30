<?php

use App\Http\Controllers\Web\Mobile\MobileController;
use Illuminate\Support\Facades\Route;

// Versión móvil de la web (solo consulta): /m
Route::middleware(['auth', 'verified'])->prefix('m')->name('mobile.')->group(function () {
    Route::get('/', [MobileController::class, 'activity'])->name('activity');
    Route::get('notificaciones', [MobileController::class, 'notifications'])->name('notifications');
    Route::post('notificaciones/leer-todas', [MobileController::class, 'readAllNotifications'])->name('notifications.readAll');
    Route::post('notificaciones/{id}/leer', [MobileController::class, 'readNotification'])->whereUuid('id')->name('notifications.read');
    Route::get('proyectos', [MobileController::class, 'projects'])->name('projects');
    Route::get('cuenta', [MobileController::class, 'account'])->name('account');
    Route::get('completa', [MobileController::class, 'useFullVersion'])->name('full');
    Route::get('activar', [MobileController::class, 'useMobileVersion'])->name('enable');
});
