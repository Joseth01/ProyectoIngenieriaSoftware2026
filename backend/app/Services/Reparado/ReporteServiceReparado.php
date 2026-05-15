<?php
// ✅ REPARACIÓN ISP — ReporteService depende solo de la interfaz que usa
// Archivo: app/Services/Reparado/ReporteServiceReparado.php
//
// VIOLACIÓN ORIGINAL (app/Services/ReporteService.php):
//
//   class ReporteService {
//       public function __construct(
//           private readonly IAnimalRepository $animalRepository,  // ← interfaz gorda
//           ...
//       ) {}
//   }
//
// IAnimalRepository incluye save() y delete(). ReporteService no los usa,
// pero depende de una interfaz que los promete. Si IAnimalRepository cambia
// su contrato de escritura (ej: firma de save() cambia), ReporteService
// podría verse afectado aunque no tenga nada que ver con escritura.

namespace App\Services\Reparado;

use App\Domain\Animales\IAnimalLector;   // ← solo la interfaz mínima necesaria
use App\Domain\Razas\IRazaFactory;

class ReporteServiceReparado
{
    public function __construct(
        private readonly IAnimalLector $animalLector,   // solo lectura
        private readonly IRazaFactory  $razaFactory
    ) {}

    public function reportePorFinca(int $fincaId): array
    {
        $animales = $this->animalLector->findAllByFinca($fincaId);

        return array_map(function ($animal) {
            $raza    = $animal->raza;
            $pesajes = $animal->pesajes ?? collect();

            $razaDominio = null;
            try {
                $razaDominio = $this->razaFactory->create($raza?->nombre ?? 'brahman');
            } catch (\InvalidArgumentException) {
                // raza sin implementación de dominio todavía
            }

            $ultimoPeso = $pesajes->sortByDesc('fecha')->first()?->peso_estimado;
            $icc = $razaDominio && $ultimoPeso
                ? $razaDominio->calcularICC($ultimoPeso)
                : null;

            return [
                'arete'         => $animal->numero_arete,
                'nombre'        => $animal->nombre,
                'raza'          => $raza?->nombre,
                'icc'           => $icc,
                'total_pesajes' => $pesajes->count(),
                'ultimo_peso'   => $ultimoPeso,
            ];
        }, $animales);
    }
}
