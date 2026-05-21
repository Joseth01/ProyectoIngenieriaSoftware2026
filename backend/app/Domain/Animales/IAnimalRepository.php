<?php

// Solo los repositorios concretos (EloquentAnimalRepository, InMemoryAnimalRepository)
// implementan esta interfaz. Los SERVICIOS dependen de la interfaz mínima que necesitan:
//   - AnimalService  → IAnimalLector + IAnimalEscritor 
//   - ReporteService → solo IAnimalLector

namespace App\Domain\Animales;

use App\Models\Animal;

/**
 * Repository Interface — habla el lenguaje del dominio, sin rastro de Eloquent.
 *
 * ReporteService y AnimalService dependen de ESTA interfaz.
 * Cambiar el ORM (Eloquent → Doctrine) = nueva clase, cero cambios en servicios.
 */
interface IAnimalRepository extends IAnimalLector, IAnimalEscritor
{
    
}
