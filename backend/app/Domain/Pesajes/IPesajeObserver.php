<?php

namespace App\Domain\Pesajes;

use App\Models\Pesaje;

/**
 * Observer (interfaz) del patrón Observer.
 *
 * Cada subsistema que reaccione al registro de un pesaje
 * debe implementar esta interfaz. PesajeSubject no conoce
 * las clases concretas — solo esta abstracción.
 */
interface IPesajeObserver
{
    public function onPesajeRegistrado(Pesaje $pesaje): void;
}
