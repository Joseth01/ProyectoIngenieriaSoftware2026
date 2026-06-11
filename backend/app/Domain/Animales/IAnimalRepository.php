<?php

namespace App\Domain\Animales;

use App\Models\Animal;

/**
 * Repository Interface — habla el lenguaje del dominio, sin rastro de Eloquent.
 *
 * ReporteService y AnimalService dependen de ESTA interfaz.
 * Cambiar el ORM (Eloquent → Doctrine) = nueva clase, cero cambios en servicios.
 */
interface IAnimalRepository
{
    public function findByArete(string $arete): ?Animal;

    public function findAllByFinca(int $fincaId): array;

    public function findWithPesajes(int $id): ?Animal;

    public function save(Animal $animal): void;

    public function delete(int $id): void;

    public function all(): array;
}
