<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ServicioIA
{
    private string $urlServicioIA;

    public function __construct()
    {
        // URL del microservicio Flask en Render/Railway.
        // Configurar YOLO_SERVICE_URL en el .env una vez desplegado.
        $this->urlServicioIA = env('YOLO_SERVICE_URL', '');
    }

    /**
     * @param  string  $rutaRelativa  Ruta relativa dentro del disco local (p.ej. "pesajes/abc.jpg")
     * @param  string  $raza          Nombre de la raza (brahman, holstein, angus…)
     * @param  int     $edadMeses     Edad del animal en meses
     */
    public function analizarImagen(
        string $rutaRelativa,
        string $raza       = 'brahman',
        int    $edadMeses  = 0
    ): array {
        if (!Storage::exists($rutaRelativa)) {
            Log::warning('[ServicioIA] Imagen no encontrada en storage', [
                'ruta' => $rutaRelativa,
            ]);
            throw new \RuntimeException('No se pudo leer la imagen subida.');
        }

        if (empty($this->urlServicioIA)) {
            throw new \RuntimeException(
                'El servicio de IA no está configurado. ' .
                'Despliega el microservicio Flask y configura YOLO_SERVICE_URL en el .env.'
            );
        }

        $respuesta = Http::timeout(60)
            ->attach('imagen', Storage::get($rutaRelativa), basename($rutaRelativa))
            ->post($this->urlServicioIA, [
                'raza' => $raza,
                'edad' => $edadMeses,
            ]);

        // Error de validación de imagen (bovino no detectado, ángulo incorrecto, etc.)
        if ($respuesta->status() === 422) {
            $cuerpo = $respuesta->json();
            throw new \RuntimeException(
                $cuerpo['error'] ?? 'La imagen no es válida para estimación de peso.'
            );
        }

        if (!$respuesta->successful()) {
            Log::error('[ServicioIA] Error del microservicio Flask', [
                'status' => $respuesta->status(),
                'body'   => $respuesta->body(),
            ]);
            throw new \RuntimeException(
                'El servicio de IA no respondió correctamente (HTTP ' . $respuesta->status() . ').'
            );
        }

        $datos = $respuesta->json();

        if (!is_array($datos)) {
            throw new \RuntimeException('La respuesta del servicio de IA no tiene formato válido.');
        }

        return $this->normalizarRespuesta($datos);
    }

    private function normalizarRespuesta(array $datos): array
    {
        $pesoEstimado =
            $datos['peso_estimado'] ??
            $datos['peso']          ??
            $datos['peso_kg']       ??
            null;

        if ($pesoEstimado === null) {
            throw new \RuntimeException('El servicio de IA no devolvió un peso estimado.');
        }

        return [
            'peso_estimado'      => round((float) $pesoEstimado, 2),
            'confianza'          => round((float) ($datos['confianza'] ?? 90), 2),
            'condicion_corporal' => isset($datos['condicion_corporal'])
                                        ? round((float) $datos['condicion_corporal'], 2)
                                        : null,
            'alzada_estimada'    => isset($datos['alzada_estimada'])
                                        ? round((float) $datos['alzada_estimada'], 2)
                                        : null,
            'mensaje'            => $datos['mensaje'] ?? 'Estimación generada correctamente',
        ];
    }
}
