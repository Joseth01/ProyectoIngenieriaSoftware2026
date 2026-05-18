<?php

namespace App\Domain\Razas;

/**
 * ConcreteProduct: Brahman
 * Parámetros zootécnicos reales de la raza Brahman usados en BovWeight CR.
 */
class Brahman extends RazaDominio
{
    public function getNombre(): string        { return 'Brahman'; }
    public function getPesoPromedioKg(): float { return 450.0; }
    public function getCoeficienteICC(): float { return 2.8; }
    public function getMesesMaduracion(): int   { return 30; }
}
