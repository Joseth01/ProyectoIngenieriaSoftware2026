<?php

namespace App\Domain\Razas;

/**
 * ConcreteProduct: Angus
 * Nueva raza agregada por SENASA — requirió CERO cambios en RazaFactory
 * (solo agregar esta clase y una línea en el array del factory).
 */
class Angus extends RazaDominio
{
    public function getNombre(): string        { return 'Angus'; }
    public function getPesoPromedioKg(): float { return 500.0; }
    public function getCoeficienteICC(): float { return 3.1; }
    public function getMesesMaduracion(): int   { return 24; }
}
