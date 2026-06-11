<?php

namespace App\Domain\Estimacion;

/**
 * Value Object del patrón Strategy.
 *
 * Inmutable por diseño: readonly garantiza que ningún observador
 * ni servicio externo puede alterar el resultado después de crearlo.
 * Sin setters — el estado se define en el constructor y no cambia.
 */
final class ResultadoEstimacion
{
    public function __construct(
        public readonly float  $pesoKg,
        public readonly float  $confianzaPorcentaje,
        public readonly string $metodoUsado
    ) {}

    public function toArray(): array
    {
        return [
            'peso_estimado'       => $this->pesoKg,
            'confianza_porcentaje' => $this->confianzaPorcentaje,
            'metodo_usado'        => $this->metodoUsado,
        ];
    }
}
