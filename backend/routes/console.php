<?php

use App\Services\MaintenanceService;
use App\Services\TrashService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vacía lo que lleva más de 30 días en la papelera (también se hace al abrir la papelera)
Artisan::command('codeflow:empty-trash', function (TrashService $trash) {
    $this->info('Elementos borrados definitivamente: '.$trash->purgeExpired());
})->purpose('Borra definitivamente lo que lleva más de 30 días en la papelera');

// Todas las tareas diarias (papelera, invitaciones caducadas, sesiones…). En Vercel: /api/cron/daily
Artisan::command('codeflow:daily', function (MaintenanceService $maintenance) {
    foreach ($maintenance->daily() as $task => $count) {
        $this->line("{$task}: {$count}");
    }
})->purpose('Tareas diarias de mantenimiento');

Schedule::command('codeflow:daily')->dailyAt('04:00');
