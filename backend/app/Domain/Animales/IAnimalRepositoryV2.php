<?php
// ✅ REPARACIÓN ISP — interfaz compuesta para quien necesita ambas operaciones
// Archivo: app/Domain/Animales/IAnimalRepositoryV2.php
//
// Solo los repositorios concretos (EloquentAnimalRepository, InMemoryAnimalRepository)
// implementan esta interfaz. Los SERVICIOS dependen de la interfaz mínima que necesitan:
//   - AnimalService  → IAnimalLector + IAnimalEscritor (o IAnimalRepositoryV2)
//   - ReporteService → solo IAnimalLector

namespace App\Domain\Animales;

interface IAnimalRepositoryV2 extends IAnimalLector, IAnimalEscritor
{
    // Vacío: hereda los dos contratos cohesivos.
}
