<?php

use App\Models\Branch;
use App\Models\Project;
use App\Models\Repository;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web de CodeFlow (Blade)
|--------------------------------------------------------------------------
| Las rutas se agrupan por módulo en routes/web/*.php.
| Las lecturas de repositorios públicos no requieren sesión: la Policy decide.
*/

Route::model('project', Project::class);
Route::model('repository', Repository::class);
Route::model('branch', Branch::class);

require __DIR__.'/web/auth.php';
require __DIR__.'/web/account.php';
require __DIR__.'/web/code.php';
require __DIR__.'/web/collab.php';
