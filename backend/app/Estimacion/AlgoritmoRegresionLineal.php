<?php

namespace App\Estimacion;

use App\Domain\Estimacion\IAlgoritmoEstimacion;
use App\Domain\Estimacion\ResultadoEstimacion;

/**
 * ConcreteStrategy 2: estimación por regresión lineal con medidas morfométricas.
 *
 * Fórmula basada en la ecuación de Schaeffer adaptada para ganado cebú:
 * peso ≈ (largo_corporal × perímetro_torácico²) / 10.840
 */
class AlgoritmoRegresionLineal implements IAlgoritmoEstimacion
{
    public function ejecutar(array $datosEntrada): ResultadoEstimacion
    {
        $largoCm      = $datosEntrada['largo_corporal_cm'] ?? 150.0;
        $perimetro    = $datosEntrada['perimetro_toracico_cm'] ?? 180.0;

        // Ecuación de Schaeffer simplificada
        $pesoKg = ($largoCm * ($perimetro ** 2)) / 10840.0;

        return new ResultadoEstimacion(
            pesoKg: round($pesoKg, 1),
            confianzaPorcentaje: 78.5,
            metodoUsado: 'RegresionLineal'
        );
    }
}
