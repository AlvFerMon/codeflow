<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('default_branch', 100)->default('main');
            $table->timestamps();
            $table->unique(['project_id', 'name']);
        });

        // Contenido de archivos deduplicado por repositorio
        Schema::create('blobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->string('hash', 40);
            $table->longText('content');
            $table->timestamps();
            $table->unique(['repository_id', 'hash']);
        });

        // Los ids de commit padre no llevan FK para simplificar el borrado en cascada
        Schema::create('commits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->unsignedBigInteger('second_parent_id')->nullable(); // solo en commits de fusion
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sha', 40);
            $table->text('message');
            $table->timestamps();
            $table->unique(['repository_id', 'sha']);
        });

        // Snapshot completo del árbol de archivos de cada commit
        Schema::create('commit_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commit_id')->constrained()->cascadeOnDelete();
            $table->string('path', 255);
            $table->foreignId('blob_id')->constrained('blobs')->cascadeOnDelete();
            $table->unique(['commit_id', 'path']);
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->unsignedBigInteger('head_commit_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['repository_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
        Schema::dropIfExists('commit_files');
        Schema::dropIfExists('commits');
        Schema::dropIfExists('blobs');
        Schema::dropIfExists('repositories');
    }
};
