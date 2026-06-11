<?php
// ✅ REPARACIÓN LSP — InMemoryAnimalRepository con comportamiento equivalente
// Archivo: app/Infrastructure/Repositories/InMemoryAnimalRepositoryReparado.php
//
// VIOLACIÓN ORIGINAL (app/Infrastructure/Repositories/InMemoryAnimalRepository.php):
//
//   public function findAllByFinca(int $fincaId): array
//   {
//       return array_values(array_filter(
//           $this->store,
//           fn(Animal $a) => $a->finca_id === $fincaId
//       ));
//   }
//
// EloquentAnimalRepository::findAllByFinca() devuelve animales CON relaciones
// 'raza' y 'pesajes' cargadas (Animal::with(['raza','pesajes'])->...->all()).
//
// InMemoryAnimalRepository::findAllByFinca() devuelve los mismos modelos pero
// SIN esas relaciones. Cuando ReporteService llama $animal->raza?->nombre
// o $animal->pesajes->count(), obtiene null/0 en tests pero datos reales en
// producción. El test pasa en verde aunque el reporte esté roto.
//
// Contrato violado: IAnimalRepository::findAllByFinca() promete devolver
// animales con sus datos suficientes para que el código cliente funcione.
// La subclase InMemory entrega un resultado estructuralmente diferente.
//
// La reparación consiste en que InMemory también adjunte manualmente los
// datos de raza y pesajes a cada animal, manteniendo el contrato conductual.

namespace App\Infrastructure\Repositories;

use App\Domain\Animales\IAnimalRepositoryV2;
use App\Models\Animal;
use App\Models\Raza;
use Illuminate\Database\Eloquent\Collection;

class InMemoryAnimalRepositoryReparado implements IAnimalRepositoryV2
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
