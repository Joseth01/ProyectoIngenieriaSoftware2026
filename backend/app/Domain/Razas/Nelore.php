<?php

namespace App\Domain\Razas;

/**
 * ConcreteProduct: Nelore
 */
class Nelore extends RazaDominio
{
    public function getNombre(): string        { return 'Nelore'; }
    public function getPesoPromedioKg(): float { return 420.0; }
    public function getCoeficienteICC(): float { return 2.6; }
    public function getMesesMaduracion(): int   { return 28; }
}
