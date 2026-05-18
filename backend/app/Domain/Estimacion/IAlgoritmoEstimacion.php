<?php

namespace App\Domain\Estimacion;

/**
 * Strategy (interfaz) del patrón Strategy.
 *
 * EstimadorPesoService depende SOLO de esta interfaz.
 * Agregar un nuevo algoritmo = nueva clase que implementa ejecutar().
 * El service nunca se modifica.
 */
interface IAlgoritmoEstimacion
{
    public function ejecutar(array $datosEntrada): ResultadoEstimacion;
}
