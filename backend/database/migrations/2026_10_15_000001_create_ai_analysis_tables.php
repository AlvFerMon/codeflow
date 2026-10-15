<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revisión de pull requests con IA (opcional). Solo se guardan el resumen y los hallazgos:
 * nunca el código del PR (que está cifrado y se descifra en memoria durante el análisis).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pull_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            // pending → running → done | failed
            $table->string('status', 16)->default('pending');
            $table->string('model', 100);
            // Commit de la rama origen analizado (para avisar si el PR ha cambiado después)
            $table->foreignId('head_commit_id')->nullable()->constrained('commits')->nullOnDelete();
            $table->text('summary')->nullable();
            $table->string('error', 500)->nullable();
            $table->unsignedSmallInteger('files_analyzed')->default(0);
            $table->unsignedSmallInteger('files_skipped')->default(0);
            $table->unsignedSmallInteger('findings_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['pull_request_id', 'id']);
        });

        Schema::create('ai_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_analysis_id')->constrained('ai_analyses')->cascadeOnDelete();
            $table->string('path', 1024);
            $table->unsignedInteger('line')->nullable();
            // high | medium | low
            $table->string('severity', 10);
            // bug | security | performance | style | maintainability
            $table->string('category', 20);
            $table->string('title', 200);
            $table->text('explanation');
            $table->text('suggestion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_findings');
        Schema::dropIfExists('ai_analyses');
    }
};
