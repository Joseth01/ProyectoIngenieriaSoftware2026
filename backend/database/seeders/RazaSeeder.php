<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RazaSeeder extends Seeder
{
    public function run(): void
    {
        // Tabla catálogo — solo insertar si está vacía
        if (DB::table('razas')->count() > 0) {
            return;
        }

        DB::table('razas')->insert([
            ['nombre' => 'Brahman', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Nelore',  'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Angus',   'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
