<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vacía lo que lleva más de 30 días en la papelera (también se hace al abrir la papelera)
Artisan::command('codeflow:empty-trash', function (\App\Services\TrashService $trash) {
    $this->info('Elementos borrados definitivamente: '.$trash->purgeExpired());
})->purpose('Borra definitivamente lo que lleva más de 30 días en la papelera');

\Illuminate\Support\Facades\Schedule::command('codeflow:empty-trash')->dailyAt('04:00');
