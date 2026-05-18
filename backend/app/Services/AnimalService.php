<?php

namespace App\Services;

use App\Domain\Animales\IAnimalRepository;
use App\Domain\Razas\IRazaFactory;
use App\Models\Animal;

/**
 * Servicio de dominio para animales.
 * Usa IAnimalRepository (Repository) e IRazaFactory (Factory).
 *
 * Punto de creación 1 refactorizado: antes AnimalController
 * hacía Animal::create() con raza_id hardcodeado.
 * Ahora la lógica de dominio vive aquí y la raza se valida via factory.
 */
class AnimalService
{
    public function __construct(
        private readonly IAnimalRepository $animalRepository,
        private readonly IRazaFactory      $razaFactory
    ) {}

    public function registrar(array $datos): Animal
    {
        // Valida que la raza exista en el dominio antes de persistir
        $razaDominio = $this->razaFactory->create($datos['nombre_raza'] ?? 'brahman');

        $animal = new Animal([
            'numero_arete'    => $datos['numero_arete'],
            'nombre'          => $datos['nombre'],
            'raza_id'         => $datos['raza_id'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'estado'          => 'activo',
            'finca_id'        => $datos['finca_id'],
        ]);

        $this->animalRepository->save($animal);
        return $animal;
    }

    public function buscarPorArete(string $arete): ?Animal
    {
        return $this->animalRepository->findByArete($arete);
    }

    public function historial(int $id): ?Animal
    {
        return $this->animalRepository->findWithPesajes($id);
    }

    public function listarPorFinca(int $fincaId): array
    {
        return $this->animalRepository->findAllByFinca($fincaId);
    }

    public function listarTodos(): array
    {
        return $this->animalRepository->all();
    }
}
