<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Raza;

class RazaSeeder extends Seeder
{
    public function run(): void
    {
        $razas = ['Brahman', 'Nelore', 'Angus'];

        foreach ($razas as $nombre) {
            Raza::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
