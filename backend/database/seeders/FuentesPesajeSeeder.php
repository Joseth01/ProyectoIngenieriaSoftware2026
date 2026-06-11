<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FuentesPesajeSeeder extends Seeder
{
    public function run(): void
    {
        // Tabla catálogo — solo insertar si está vacía
        if (DB::table('fuentes_pesaje')->count() > 0) {
            return;
        }

        DB::table('fuentes_pesaje')->insert([
            ['nombre' => 'Báscula oficial',   'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Estimación IA',     'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Cinta métrica',     'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Estimación visual', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
