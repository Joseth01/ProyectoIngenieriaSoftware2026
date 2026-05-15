<?php

namespace App\Domain\Razas;

/**
 * Creator (interfaz) del patrón Factory Method.
 * El código cliente depende SOLO de esta interfaz, nunca de RazaFactory directamente.
 */
interface IRazaFactory
{
    public function create(string $nombreRaza): RazaDominio;
}
