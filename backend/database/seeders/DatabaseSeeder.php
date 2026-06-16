<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Catálogos base (idempotentes): razas y fuentes de pesaje
        $this->call([
            RazaSeeder::class,
            FuentesPesajeSeeder::class,
        ]);

        // Usuario de prueba (solo si no existe)
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password123', 'rol' => 'ganadero']
        );
    }
}
