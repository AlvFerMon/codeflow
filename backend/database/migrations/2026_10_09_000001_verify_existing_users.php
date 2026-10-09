<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Al activar la verificación de email, las cuentas que ya existían se dan por verificadas
 * para no bloquear a nadie. Solo las cuentas nuevas tendrán que confirmar su correo.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // No se puede saber qué cuentas estaban sin verificar: no se deshace.
    }
};
