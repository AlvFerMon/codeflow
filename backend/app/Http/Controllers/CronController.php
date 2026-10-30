<?php

namespace App\Http\Controllers;

use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vercel Cron llama a GET /api/cron/daily con la cabecera «Authorization: Bearer $CRON_SECRET»
 * (la variable CRON_SECRET se define en el panel de Vercel). Sin secreto configurado no hace nada.
 */
class CronController extends Controller
{
    public function daily(Request $request, MaintenanceService $maintenance): JsonResponse
    {
        $secret = (string) config('codeflow.cron_secret');
        abort_if($secret === '' || ! hash_equals('Bearer '.$secret, (string) $request->header('Authorization')), 401);

        return response()->json(['ok' => true, 'done' => $maintenance->daily()]);
    }
}
