<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos iniciales: usuario administrador + datos de demostración.
     * Es idempotente: si los usuarios ya existen no duplica nada.
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
