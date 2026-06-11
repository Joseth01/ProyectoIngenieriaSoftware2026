<?php

namespace App\Services;

use App\Domain\Estimacion\IAlgoritmoEstimacion;
use App\Domain\Estimacion\ResultadoEstimacion;
use App\Estimacion\AlgoritmoTablaReferencia;
use Illuminate\Support\Facades\Log;

/**
 * Context del patrón Strategy.
 *
 * ANTES (código problemático):
 *   if ($metodo === 'yolov8') { ... }
 *   elseif ($metodo === 'regresion') { ... }
 *   elseif ($metodo === 'tabla') { ... }
 *
 * DESPUÉS: cero if-else. El service delega completamente a la estrategia
 * inyectada. Cambiar el algoritmo en runtime = llamar setAlgoritmo().
 */
class EstimadorPesoService
{
    private IAlgoritmoEstimacion $algoritmo;

    public function __construct(IAlgoritmoEstimacion $algoritmo)
    {
        $this->algoritmo = $algoritmo;
    }

    public function setAlgoritmo(IAlgoritmoEstimacion $algoritmo): void
    {
        $this->algoritmo = $algoritmo;
    }

    public function estimar(array $datosEntrada): ResultadoEstimacion
    {
        try {
            return $this->algoritmo->ejecutar($datosEntrada);
        } catch (\Exception $e) {
            // Fallback automático a TablaReferencia cuando YOLOv8 pierde conexión
            Log::warning('[EstimadorPesoService] Algoritmo principal falló, activando fallback TablaReferencia: ' . $e->getMessage());
            $this->setAlgoritmo(new AlgoritmoTablaReferencia());
            return $this->algoritmo->ejecutar($datosEntrada);
        }
    }
}
