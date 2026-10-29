<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Producción (CODEFLOW_SEED_DEMO=false): crea solo el administrador inicial, sin datos de prueba.
 * Idempotente: si ya existe un usuario con ese email no hace nada (no cambia su contraseña).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD vacíos: no se crea el administrador.');

            return;
        }

        if (User::where('email', $email)->exists()) {
            return;
        }

        $admin = new User;
        $admin->forceFill([
            'name' => 'Administrador',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_admin' => true,
            'is_active' => true,
        ])->save();

        $this->command?->info("Administrador creado: {$email}");
    }
}
