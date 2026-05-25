<?php

namespace App\Http\Controllers;

use App\Domain\Pesajes\PesajeSubject;
use App\Estimacion\AlgoritmoRegresionLineal;
use App\Estimacion\AlgoritmoTablaReferencia;
use App\Estimacion\AlgoritmoYolov8;
use App\Models\Pesaje;
use App\Observers\AlertaSMS;
use App\Observers\NotificadorPropietario;
use App\Observers\RecalculadorICC;
use App\Observers\WebhookSenasa;
use App\Services\EstimadorPesoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

/**
 * ANTES: Pesaje::create($datos) directo — sin observers, sin estimación estratégica.
 *
 * DESPUÉS: usa PesajeSubject (Observer) para notificar y EstimadorPesoService (Strategy)
 * para calcular el peso antes de persistir.
 *
 * El campo 'metodo_estimacion' en el request determina la estrategia en runtime:
 *   - 'yolov8'    → AlgoritmoYolov8 (con fallback automático a TablaReferencia)
 *   - 'regresion' → AlgoritmoRegresionLineal
 *   - 'tabla'     → AlgoritmoTablaReferencia
 */
class PesajeController extends Controller
{
    public function listar(): JsonResponse
    {
        $pesajes = Pesaje::with('animal')->orderBy('fecha', 'desc')->get();

        return ApiResponse::success(
            'Pesajes obtenidos correctamente',
         $pesajes   
         );
    }

    public function obtenerPorAnimal(int $animal_id): JsonResponse
    {
        $pesajes = Pesaje::with('animal')->where('animal_id', $animal_id)->orderBy('fecha', 'desc')->get();

        return ApiResponse::success(
        'Pesajes del animal obtenidos correctamente',
        $pesajes
        );
    }

    public function crear(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'animal_id'          => 'required|exists:animales,id',
            'fecha'              => 'required|date',
            'fuente_id'          => 'nullable|exists:fuentes_pesaje,id',
            'peso_real'          => 'nullable|numeric|min:0',
            'metodo_estimacion'  => 'required|in:yolov8,regresion,tabla',
            // Datos para estimación
            'raza'               => 'nullable|string',
            'edad_meses'         => 'nullable|integer|min:1',
            'largo_corporal_cm'  => 'nullable|numeric',
            'perimetro_toracico_cm' => 'nullable|numeric',
            'peso_referencia'    => 'nullable|numeric',
        ]);

        // --- PATRÓN STRATEGY: selección del algoritmo según metodo_estimacion ---
        $algoritmo = match ($datos['metodo_estimacion']) {
            'regresion' => new AlgoritmoRegresionLineal(),
            'tabla'     => new AlgoritmoTablaReferencia(),
            default     => new AlgoritmoYolov8(),
        };

        $estimador  = new EstimadorPesoService($algoritmo);
        $resultado  = $estimador->estimar($datos);

        // --- PATRÓN OBSERVER: construir pesaje y notificar a suscriptores ---
        $pesaje = new Pesaje([
            'animal_id'     => $datos['animal_id'],
            'peso_estimado' => $resultado->pesoKg,
            'peso_real'     => $datos['peso_real'] ?? null,
            'fecha'         => $datos['fecha'],
            'fuente_id'     => $datos['fuente_id'] ?? null,
        ]);

        $subject = new PesajeSubject();
        $subject->suscribir(new NotificadorPropietario());
        $subject->suscribir(new RecalculadorICC());
        $subject->suscribir(new WebhookSenasa());
        $subject->suscribir(new AlertaSMS());   // cuarto observador sin tocar los anteriores

        $pesaje = $subject->registrar($pesaje); // persiste Y notifica

        return ApiResponse::success(
            'Pesaje registrado correctamente',
            array_merge(
            $pesaje->toArray(),
            ['estimacion' => $resultado->toArray()]
            ),
             201
        );
    }

    public function obtener(int $id): JsonResponse
    {
        $pesaje = Pesaje::find($id);

        if (!$pesaje) {
        return ApiResponse::error(
            'Pesaje no encontrado',
            [],
         404
        );
        }
        return ApiResponse::success(
        'Pesaje obtenido correctamente',
         $pesaje
        );
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $pesaje = Pesaje::findOrFail($id);

        $datos = $request->validate([
            'peso_estimado' => 'required|numeric|min:0',
            'peso_real'     => 'nullable|numeric|min:0',
            'fecha'         => 'required|date',
            'fuente_id'     => 'nullable|exists:fuentes_pesaje,id',
        ]);

        $pesaje->update($datos);

        return ApiResponse::success(
        'Pesaje actualizado correctamente',
            $pesaje
        );
    }

    public function eliminar(int $id): JsonResponse
    {
        $pesaje = Pesaje::findOrFail($id);
        $pesaje->delete();

        return ApiResponse::success(
        'Pesaje eliminado correctamente'
        );
    }
    public function estimarPeso(Request $request)
{
    $request->validate([
        'imagen' => 'required|image'
    ]);

    $ruta = $request->file('imagen')->store('pesajes');

    $servicioIA = new ServicioIA();

    $resultado = $servicioIA->analizarImagen(
        storage_path('app/' . $ruta)
    );

    return response()->json($resultado);
}
}
