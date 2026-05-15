<?php

namespace App\Domain\Razas;

/**
 * Product (abstracción) del patrón Factory Method.
 * Representa el COMPORTAMIENTO de una raza, no solo su nombre en BD.
 * AnimalController y ReporteService dependen de esta abstracción,
 * nunca de Brahman o Nelore directamente.
 */
abstract class RazaDominio
{
    abstract public function getNombre(): string;
    abstract public function getPesoPromedioKg(): float;
    abstract public function getCoeficienteICC(): float;
    abstract public function getMesesMaduracion(): int;

    public function calcularICC(float $pesoActualKg): float
    {
        return round($pesoActualKg / $this->getPesoPromedioKg() * $this->getCoeficienteICC(), 2);
    }
}
