<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Animales\IAnimalRepository;
use App\Models\Animal;

/**
 * Concrete Repository — implementación con Eloquent.
 *
 * Es el ÚNICO lugar donde vive Animal::where(), Animal::with(), etc.
 * Agregar caché Redis = modificar solo este archivo.
 * Intercambiable por DoctrineAnimalRepository sin tocar ningún servicio.
 */
class EloquentAnimalRepository implements IAnimalRepository
{
    public function findByArete(string $arete): ?Animal
    {
        return Animal::where('numero_arete', $arete)->first();
    }

    public function findAllByFinca(int $fincaId): array
    {
        // Punto único de consulta — si mañana se agrega Redis, se agrega aquí
        return Animal::where('finca_id', $fincaId)
            ->with(['raza', 'pesajes'])
            ->get()
            ->all();
    }

    public function findWithPesajes(int $id): ?Animal
    {
        return Animal::with('pesajes')->find($id);
    }

    public function save(Animal $animal): void
    {
        $animal->save();
    }

    public function delete(int $id): void
    {
        Animal::destroy($id);
    }

    public function all(): array
    {
        return Animal::with('raza')->get()->all();
    }
}
