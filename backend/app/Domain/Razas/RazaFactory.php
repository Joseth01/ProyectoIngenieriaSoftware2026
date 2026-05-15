<?php

namespace App\Domain\Razas;

use InvalidArgumentException;

/**
 * ConcreteCreator del patrón Factory Method.
 *
 * Usa array asociativo en lugar de switch para cumplir Open/Closed:
 * agregar Angus = una línea en $mapa, sin tocar ningún controlador.
 *
 * Registrado como singleton en AppServiceProvider para que el mapa
 * se construya una sola vez por request.
 */
class RazaFactory implements IRazaFactory
{
    private array $mapa = [
        'brahman' => Brahman::class,
        'nelore'  => Nelore::class,
        'angus'   => Angus::class,
    ];

    public function create(string $nombreRaza): RazaDominio
    {
        $clave = strtolower(trim($nombreRaza));

        if (!isset($this->mapa[$clave])) {
            throw new InvalidArgumentException(
                "Raza '{$nombreRaza}' no reconocida. Razas válidas: " . implode(', ', array_keys($this->mapa))
            );
        }

        $clase = $this->mapa[$clave];
        return new $clase();
    }

    public function razasDisponibles(): array
    {
        return array_keys($this->mapa);
    }
}
