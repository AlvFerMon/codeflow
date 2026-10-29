<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estado del tutorial de bienvenida (visita guiada + lista «Primeros pasos») de cada usuario.
 * Las cuentas que ya existían no ven la visita guiada: se marca como completada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('onboarding')->nullable();
        });

        DB::table('users')->update([
            'onboarding' => json_encode(['tour_status' => 'completed', 'tour_finished_at' => now()->toIso8601String(), 'legacy' => true]),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarding');
        });
    }
};
