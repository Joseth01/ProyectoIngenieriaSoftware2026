<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Animales\IAnimalRepositoryV2;
use App\Models\Animal;

/**
 * Concrete Repository — implementación con Eloquent.
 *
 * Implementa IAnimalRepositoryV2 (= IAnimalLector + IAnimalEscritor).
 * Sustituible por DoctrineAnimalRepository o InMemoryAnimalRepositoryReparado
 * sin tocar ningún servicio.
 */
class EloquentAnimalRepository implements IAnimalRepositoryV2
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
        return Animal::with(['raza', 'finca', 'pesajes'])->get()->all();
    }
}
