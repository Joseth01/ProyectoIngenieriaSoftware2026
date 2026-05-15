<?php

namespace App\Estimacion;

use App\Domain\Estimacion\IAlgoritmoEstimacion;
use App\Domain\Estimacion\ResultadoEstimacion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ConcreteStrategy 1: estimación por visión computacional YOLOv8.
 *
 * Simula la llamada HTTP al microservicio de IA.
 * Si el servicio no responde, EstimadorPesoService activa el fallback
 * a AlgoritmoTablaReferencia (demostración de cambio en runtime).
 */
class AlgoritmoYolov8 implements IAlgoritmoEstimacion
{
    public function __construct(
        private readonly string $urlServicio = 'http://yolov8-service/predict'
    ) {}

    public function ejecutar(array $datosEntrada): ResultadoEstimacion
    {
        // Simulación de llamada HTTP — en producción: Http::post($this->urlServicio, $datosEntrada)
        $simulado = $this->simularRespuesta($datosEntrada);

        Log::info('[AlgoritmoYolov8] Estimación completada', $simulado);

        return new ResultadoEstimacion(
            pesoKg: $simulado['peso_kg'],
            confianzaPorcentaje: $simulado['confianza'],
            metodoUsado: 'YOLOv8'
        );
    }

    private function simularRespuesta(array $datos): array
    {
        // Simula variación realista basada en la imagen
        $base = $datos['peso_referencia'] ?? 350.0;
        return [
            'peso_kg'   => round($base * (0.95 + (mt_rand(0, 10) / 100)), 1),
            'confianza' => round(88.0 + (mt_rand(0, 80) / 10), 1),
        ];
    }
}
