<?php
// ✅ REPARACIÓN SRP + DIP — EstimadorPesoService con responsabilidad única
// Archivo: app/Services/Reparado/EstimadorPesoServiceReparado.php
//
// VIOLACIÓN ORIGINAL (app/Services/EstimadorPesoService.php):
//
//   public function estimar(array $datosEntrada): ResultadoEstimacion
//   {
//       try {
//           return $this->algoritmo->ejecutar($datosEntrada);
//       } catch (\Exception $e) {
//           // ← El servicio de negocio decide por su cuenta cuál es el algoritmo de fallback
//           $this->setAlgoritmo(new AlgoritmoTablaReferencia()); // ← DIP + SRP violado
//           return $this->algoritmo->ejecutar($datosEntrada);
//       }
//   }
//
// Responsabilidad 1: ejecutar la estimación (lógica de negocio) ← válida
// Responsabilidad 2: decidir la política de fallback y qué clase instanciar ← debe estar fuera
//
// Si la política cambia (3 reintentos, alerta antes del fallback, fallback diferente por
// horario), hay que abrir EstimadorPesoService — clase de negocio que no debería cambiar.

namespace App\Services\Reparado;

use App\Domain\Estimacion\IAlgoritmoEstimacion;
use App\Domain\Estimacion\ResultadoEstimacion;
use Illuminate\Support\Facades\Log;

class EstimadorPesoServiceReparado
{
    /**
     * @param IAlgoritmoEstimacion $algoritmo   algoritmo principal
     * @param IAlgoritmoEstimacion $fallback    algoritmo de respaldo, inyectado desde fuera
     *
     * Ahora EstimadorPesoService tiene UNA responsabilidad: ejecutar la estimación.
     * La política de fallback la decide quien construye este objeto (AppServiceProvider).
     */
    public function __construct(
        private IAlgoritmoEstimacion $algoritmo,
        private readonly IAlgoritmoEstimacion $fallback
    ) {}

    public function setAlgoritmo(IAlgoritmoEstimacion $algoritmo): void
    {
        $this->algoritmo = $algoritmo;
    }

    public function estimar(array $datosEntrada): ResultadoEstimacion
    {
        try {
            return $this->algoritmo->ejecutar($datosEntrada);
        } catch (\Exception $e) {
            Log::warning('[EstimadorPesoService] Algoritmo principal falló, activando fallback: ' . $e->getMessage());
            // Usa el fallback inyectado — no sabe qué clase concreta es
            return $this->fallback->ejecutar($datosEntrada);
        }
    }
}

// ── Registro en AppServiceProvider: ──────────────────────────
// $this->app->bind(EstimadorPesoServiceReparado::class, function () {
//     return new EstimadorPesoServiceReparado(
//         algoritmo: new AlgoritmoYolov8(),
//         fallback:  new AlgoritmoTablaReferencia()   // política decidida aquí, no en el servicio
//     );
// });
