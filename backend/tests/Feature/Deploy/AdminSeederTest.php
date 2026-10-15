<?php

namespace Tests\Feature\Deploy;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_solo_el_administrador_y_es_idempotente(): void
    {
        putenv('SEED_ADMIN_EMAIL=jefe@codeflow.test');
        putenv('SEED_ADMIN_PASSWORD=Clave-Segura-2026');

        try {
            $this->seed(AdminSeeder::class);
            $this->seed(AdminSeeder::class);

            $this->assertSame(1, User::count());
            $admin = User::first();
            $this->assertSame('jefe@codeflow.test', $admin->email);
            $this->assertTrue((bool) $admin->is_admin);
            $this->assertNotNull($admin->email_verified_at);
        } finally {
            putenv('SEED_ADMIN_EMAIL');
            putenv('SEED_ADMIN_PASSWORD');
        }
    }

    public function test_sin_credenciales_no_crea_nada(): void
    {
        putenv('SEED_ADMIN_EMAIL');
        putenv('SEED_ADMIN_PASSWORD');

        $this->seed(AdminSeeder::class);

        $this->assertSame(0, User::count());
    }
}
