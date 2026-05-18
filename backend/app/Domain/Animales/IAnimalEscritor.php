<?php
// ✅ REPARACIÓN ISP — interfaz cohesiva de SOLO ESCRITURA
// Archivo: app/Domain/Animales/IAnimalEscritor.php

namespace App\Domain\Animales;

use App\Models\Animal;

/**
 * Contrato de escritura — Command side (CQRS a nivel de interfaz).
 * AnimalService depende de esta interfaz para persistir cambios.
 */
interface IAnimalEscritor
{
    public function save(Animal $animal): void;
    public function delete(int $id): void;
}
