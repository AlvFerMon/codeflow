<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\PasswordResetController;
use App\Http\Controllers\Web\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// Autenticación (sesión)
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.store');

    Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    // Enlace del correo con el token como parámetro (?token=&email=): se redirige al formulario
    Route::get('reset-password', [PasswordResetController::class, 'redirectToForm'])->name('password.reset.query');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
