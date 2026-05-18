<?php

namespace App\Estimacion;

use App\Domain\Estimacion\IAlgoritmoEstimacion;
use App\Domain\Estimacion\ResultadoEstimacion;

/**
 * ConcreteStrategy 3: estimación por tabla de referencia SENASA.
 *
 * Actúa como FALLBACK cuando YOLOv8 no tiene conexión.
 * Tabla basada en promedios históricos del programa de mejoramiento genético.
 */
class AlgoritmoTablaReferencia implements IAlgoritmoEstimacion
{
    // [raza][meses_edad] => peso promedio en kg
    private array $tabla = [
        'brahman' => [6 => 150, 12 => 250, 18 => 340, 24 => 410, 30 => 450],
        'nelore'  => [6 => 140, 12 => 230, 18 => 320, 24 => 390, 30 => 420],
        'angus'   => [6 => 160, 12 => 270, 18 => 370, 24 => 450, 30 => 500],
    ];

    public function ejecutar(array $datosEntrada): ResultadoEstimacion
    {
        $raza   = strtolower($datosEntrada['raza'] ?? 'brahman');
        $meses  = (int) ($datosEntrada['edad_meses'] ?? 18);

        $pesoKg = $this->interpolar($raza, $meses);

        return new ResultadoEstimacion(
            pesoKg: $pesoKg,
            confianzaPorcentaje: 65.0,
            metodoUsado: 'TablaReferencia'
        );
    }

    private function interpolar(string $raza, int $meses): float
    {
        $tablaRaza = $this->tabla[$raza] ?? $this->tabla['brahman'];
        $edades    = array_keys($tablaRaza);

        // Busca la edad exacta o la más cercana
        if (isset($tablaRaza[$meses])) {
            return (float) $tablaRaza[$meses];
        }

        // Interpolación lineal entre los dos puntos más cercanos
        $anterior = null;
        $siguiente = null;

        foreach ($edades as $edad) {
            if ($edad <= $meses) $anterior = $edad;
            if ($edad >= $meses && $siguiente === null) $siguiente = $edad;
        }

        if ($anterior === null) return (float) $tablaRaza[min($edades)];
        if ($siguiente === null) return (float) $tablaRaza[max($edades)];
        if ($anterior === $siguiente) return (float) $tablaRaza[$anterior];

        $ratio = ($meses - $anterior) / ($siguiente - $anterior);
        return round(
            $tablaRaza[$anterior] + $ratio * ($tablaRaza[$siguiente] - $tablaRaza[$anterior]),
            1
        );
    }
}
