<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Progreso del análisis con IA (lotes hechos / total), archivos recortados y la lista de
 * archivos omitidos con su motivo (solo rutas: nunca código).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->unsignedSmallInteger('batches_total')->default(0)->after('findings_count');
            $table->unsignedSmallInteger('batches_done')->default(0)->after('batches_total');
            $table->unsignedSmallInteger('files_truncated')->default(0)->after('files_skipped');
            $table->json('skipped_files')->nullable()->after('files_truncated');
        });
    }

    public function down(): void
    {
        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->dropColumn(['batches_total', 'batches_done', 'files_truncated', 'skipped_files']);
        });
    }
};
