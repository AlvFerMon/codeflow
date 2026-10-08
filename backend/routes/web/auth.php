<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\PlaceholderController as P;
use Illuminate\Support\Facades\Route;

// Autenticación (sesión)
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
    Route::get('register', P::class)->defaults('title', 'Registro')->name('register');
    Route::post('register', P::class)->name('register.store');
    Route::get('forgot-password', P::class)->defaults('title', 'Recuperar contraseña')->name('password.request');
    Route::post('forgot-password', P::class)->name('password.email');
    Route::get('reset-password/{token}', P::class)->defaults('title', 'Nueva contraseña')->name('password.reset');
    Route::post('reset-password', P::class)->name('password.update');
});

Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
