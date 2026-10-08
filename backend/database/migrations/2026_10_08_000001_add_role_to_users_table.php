<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rol global: administrador de la plataforma (el rol dentro de cada proyecto va en project_members)
            $table->boolean('is_admin')->default(false)->after('email');
            // Un usuario desactivado no puede iniciar sesión y sus tokens dejan de ser válidos
            $table->boolean('is_active')->default(true)->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'is_active']);
        });
    }
};
