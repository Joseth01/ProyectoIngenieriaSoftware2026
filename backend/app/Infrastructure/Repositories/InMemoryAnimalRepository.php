<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Animales\IAnimalRepositoryV2;
use App\Models\Animal;
use App\Models\Raza;
use Illuminate\Database\Eloquent\Collection;

/**
 * In-Memory Repository — exclusivo para pruebas unitarias.
 *
 * Implementa la misma interfaz que Eloquent pero sin tocar la BD.
 * Permite probar AnimalService y ReporteService de forma aislada y rápida.
 */
class InMemoryAnimalRepository implements IAnimalRepository
{
     /** @var array<int, Animal> */
    private array $store = [];

    /** Datos auxiliares para simular relaciones en tests */
    private array $razas    = [];   // [raza_id => Raza]
    private array $pesajes  = [];   // [animal_id => Pesaje[]]

    // ── Helpers para preparar el estado en tests ──────────
    public function agregarRaza(Raza $raza): void
    {
        $this->razas[$raza->id] = $raza;
    }

    public function agregarPesaje(\App\Models\Pesaje $pesaje): void
    {
        $this->pesajes[$pesaje->animal_id][] = $pesaje;
    }

    // ── IAnimalLector ─────────────────────────────────────

    public function findByArete(string $arete): ?Animal
    {
        foreach ($this->store as $animal) {
            if ($animal->numero_arete === $arete) {
                return $this->cargarRelaciones($animal);
            }
        }
        return null;
    }

    public function findAllByFinca(int $fincaId): array
    {
        $resultado = array_values(array_filter(
            $this->store,
            fn(Animal $a) => $a->finca_id === $fincaId
        ));

        // ✅ Carga las relaciones manualmente para honrar el mismo contrato
        // que EloquentAnimalRepository. Ahora $animal->raza y $animal->pesajes
        // están disponibles en tests igual que en producción.
        return array_map(fn($a) => $this->cargarRelaciones($a), $resultado);
    }

    public function findWithPesajes(int $id): ?Animal
    {
        $animal = $this->store[$id] ?? null;
        return $animal ? $this->cargarRelaciones($animal) : null;
    }

    public function all(): array
    {
        return array_map(fn($a) => $this->cargarRelaciones($a), array_values($this->store));
    }

    // ── IAnimalEscritor ───────────────────────────────────

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

    public function count(): int
    {
        return count($this->store);
    }

    // ── Privado ───────────────────────────────────────────

    private function cargarRelaciones(Animal $animal): Animal
    {
        // Simula Eloquent eager-loading sin tocar la base de datos
        if (isset($this->razas[$animal->raza_id])) {
            $animal->setRelation('raza', $this->razas[$animal->raza_id]);
        }

        $pesajesAnimal = new Collection($this->pesajes[$animal->id] ?? []);
        $animal->setRelation('pesajes', $pesajesAnimal);

        return $animal;
    }
}
