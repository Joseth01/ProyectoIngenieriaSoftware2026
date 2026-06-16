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

        // Usuario administrador (solo si no existe)
        User::firstOrCreate(
            ['email' => 'admin@bovweight.cr'],
            ['name' => 'Administrador', 'password' => 'Admin1234', 'rol' => 'admin', 'activo' => true]
        );

        // Usuario veterinario de prueba (el PerfilVeterinario se autocrea al entrar)
        User::firstOrCreate(
            ['email' => 'vet@bovweight.cr'],
            ['name' => 'Dr. Veterinario', 'password' => 'Vet1234', 'rol' => 'veterinario', 'activo' => true]
        );

        // Usuario de prueba ganadero (solo si no existe)
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password123', 'rol' => 'ganadero']
        );
    }
}
