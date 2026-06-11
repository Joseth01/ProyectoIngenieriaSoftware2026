<?php
// ✅ REPARACIÓN ISP — interfaz cohesiva de SOLO LECTURA
// Archivo: app/Domain/Animales/IAnimalLector.php
//
// VIOLACIÓN ORIGINAL (app/Domain/Animales/IAnimalRepository.php):
//
//   interface IAnimalRepository {
//       public function findByArete(string $arete): ?Animal;   // lectura
//       public function findAllByFinca(int $fincaId): array;   // lectura
//       public function findWithPesajes(int $id): ?Animal;     // lectura
//       public function all(): array;                          // lectura
//       public function save(Animal $animal): void;            // ESCRITURA ← no cohesivo
//       public function delete(int $id): void;                 // ESCRITURA ← no cohesivo
//   }
//
// ReporteService solo llama a findAllByFinca() pero está obligado a depender
// de toda la interfaz, incluyendo save() y delete() que nunca usa.
// Si se agrega bulkUpdate() a IAnimalRepository, ReporteService y cualquier
// test double de solo lectura deben implementarlo — aunque no les incumbe.

namespace App\Domain\Animales;

use App\Models\Animal;

/**
 * Contrato de lectura — Query side (CQRS a nivel de interfaz).
 * ReporteService y cualquier lector de solo consulta dependen de esta interfaz.
 */
interface IAnimalLector
{
    public function findByArete(string $arete): ?Animal;
    public function findAllByFinca(int $fincaId): array;
    public function findWithPesajes(int $id): ?Animal;
    public function all(): array;
}
