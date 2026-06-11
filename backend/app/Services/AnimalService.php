<?php

namespace App\Services;

use App\Domain\Animales\IAnimalRepository;
use App\Models\Animal;
use Illuminate\Database\Eloquent\Collection;

class AnimalService
{
    public function __construct(
        private readonly IAnimalRepository $animalRepository
    ) {}

    public function registrar(array $datos): Animal
    {
        $animal = new Animal([
            'numero_arete'      => $datos['numero_arete'],
            'nombre'            => $datos['nombre'],
            'raza_id'           => $datos['raza_id'],
            'fecha_nacimiento'  => $datos['fecha_nacimiento'],
            'estado'            => 'activo',
            'finca_id'          => $datos['finca_id'],
        ]);

        $this->animalRepository->save($animal);

        return $animal->load([
            'raza',
            'finca',
        ]);
    }

    public function listarTodos(): Collection
    {
        return Animal::with([
            'raza',
            'finca',
        ])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function obtenerPorId(int $id): ?Animal
    {
        return Animal::with([
            'raza',
            'finca',
            'pesajes.fuente',
        ])->find($id);
    }

    public function buscarPorArete(string $arete): ?Animal
    {
        return Animal::with([
            'raza',
            'finca',
            'pesajes.fuente',
        ])
            ->where('numero_arete', $arete)
            ->first();
    }

    public function historial(int $id): ?Animal
    {
        return Animal::with([
            'raza',
            'finca',
            'pesajes.fuente',
        ])->find($id);
    }

    public function listarPorFinca(int $fincaId): Collection
    {
        return Animal::with([
            'raza',
            'finca',
        ])
            ->where('finca_id', $fincaId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function actualizar(Animal $animal, array $datos): Animal
    {
        $animal->update($datos);

        return $animal->load([
            'raza',
            'finca',
        ]);
    }

    public function desactivar(Animal $animal): void
    {
        $animal->estado = 'inactivo';
        $animal->save();
    }
}