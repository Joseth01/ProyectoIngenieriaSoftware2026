<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Animales\IAnimalRepository;
use App\Models\Animal;

/**
 * In-Memory Repository — exclusivo para pruebas unitarias.
 *
 * Implementa la misma interfaz que Eloquent pero sin tocar la BD.
 * Permite probar AnimalService y ReporteService de forma aislada y rápida.
 */
class InMemoryAnimalRepository implements IAnimalRepository
{
    private array $store = [];

    public function findByArete(string $arete): ?Animal
    {
        foreach ($this->store as $animal) {
            if ($animal->numero_arete === $arete) {
                return $animal;
            }
        }
        return null;
    }

    public function findAllByFinca(int $fincaId): array
    {
        return array_values(array_filter(
            $this->store,
            fn(Animal $a) => $a->finca_id === $fincaId
        ));
    }

    public function findWithPesajes(int $id): ?Animal
    {
        return $this->store[$id] ?? null;
    }

    public function save(Animal $animal): void
    {
        if (!$animal->id) {
            $animal->id = count($this->store) + 1;
        }
        $this->store[$animal->id] = $animal;
    }

    public function delete(int $id): void
    {
        unset($this->store[$id]);
    }

    public function all(): array
    {
        return array_values($this->store);
    }

    public function count(): int
    {
        return count($this->store);
    }
}
