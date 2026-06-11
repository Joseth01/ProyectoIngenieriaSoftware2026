<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ServicioIA
{
    private string $urlServicioIA = 'http://127.0.0.1:5000/detectar';

    public function analizarImagen(UploadedFile $imagen): array
    {
        if (!$imagen->isValid()) {
            throw new RuntimeException('La imagen no se recibió correctamente.');
        }

        $rutaTemporal = $imagen->getRealPath();

        if (!$rutaTemporal || !file_exists($rutaTemporal)) {
            throw new RuntimeException('No se pudo leer la imagen temporal recibida.');
        }

        $respuesta = Http::timeout(60)
            ->attach(
                'imagen',
                file_get_contents($rutaTemporal),
                $imagen->getClientOriginalName() ?: 'animal.jpg'
            )
            ->post($this->urlServicioIA);

        if (!$respuesta->successful()) {
            throw new RuntimeException(
                'El servicio de IA no respondió correctamente. Código: ' . $respuesta->status()
            );
        }

        $datos = $respuesta->json();

        if (!is_array($datos)) {
            throw new RuntimeException('La respuesta del servicio de IA no tiene formato válido.');
        }

        return $this->normalizarRespuesta($datos);
    }

    private function normalizarRespuesta(array $datos): array
    {
        $pesoEstimado =
            $datos['peso_estimado'] ??
            $datos['peso'] ??
            $datos['peso_kg'] ??
            null;

        if ($pesoEstimado === null) {
            throw new RuntimeException('El servicio de IA no devolvió un peso estimado.');
        }

        return [
            'peso_estimado' => round((float) $pesoEstimado, 2),
            'confianza' => isset($datos['confianza'])
                ? round((float) $datos['confianza'], 2)
                : 90,
            'condicion_corporal' => isset($datos['condicion_corporal'])
                ? round((float) $datos['condicion_corporal'], 2)
                : null,
            'alzada_estimada' => isset($datos['alzada_estimada'])
                ? round((float) $datos['alzada_estimada'], 2)
                : null,
            'mensaje' => $datos['mensaje'] ?? 'Estimación generada correctamente'
        ];
    }
}