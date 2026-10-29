<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Invitaciones a un proyecto para personas que aún no tienen cuenta (se aceptan al verificar ese correo). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 20);
            $table->char('token_hash', 64)->unique(); // SHA-256 del enlace (el token en claro solo va en el correo)
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['email', 'accepted_at']);
            $table->index(['project_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_invitations');
    }
};
