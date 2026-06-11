<?php

namespace App\Http\Controllers;

use App\Domain\Pesajes\PesajeSubject;
use App\Estimacion\AlgoritmoRegresionLineal;
use App\Estimacion\AlgoritmoTablaReferencia;
use App\Estimacion\AlgoritmoYolov8;
use App\Helpers\ApiResponse;
use App\Models\Pesaje;
use App\Observers\AlertaSMS;
use App\Observers\NotificadorPropietario;
use App\Observers\RecalculadorICC;
use App\Observers\WebhookSenasa;
use App\Services\EstimadorPesoService;
use App\Services\ServicioIA;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Imagen;

class PesajeController extends Controller
{
    public function __construct(
        private readonly ServicioIA $servicioIA
    ) {}

    public function listar(): JsonResponse
    {
        $pesajes = Pesaje::with([
                'animal.raza',
                'animal.finca',
                'fuente'
            ])
            ->orderBy('fecha', 'desc')
            ->get();

        return ApiResponse::success(
            'Pesajes obtenidos correctamente',
            $pesajes
        );
    }

    public function obtenerPorAnimal(int $animal_id): JsonResponse
    {
        $pesajes = Pesaje::with([
                'animal.raza',
                'animal.finca',
                'fuente'
            ])
            ->where('animal_id', $animal_id)
            ->orderBy('fecha', 'desc')
            ->get();

        return ApiResponse::success(
            'Pesajes del animal obtenidos correctamente',
            $pesajes
        );
    }

    public function crear(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'animal_id' => 'required|exists:animales,id',
            'fecha' => 'required|date',
            'fuente_id' => 'nullable|exists:fuentes_pesaje,id',

            'peso_estimado' => 'nullable|numeric|min:0',
            'peso_real' => 'nullable|numeric|min:0',

            'metodo_estimacion' => 'nullable|in:yolov8,regresion,tabla',

            'raza' => 'nullable|string',
            'edad_meses' => 'nullable|integer|min:1',
            'largo_corporal_cm' => 'nullable|numeric',
            'perimetro_toracico_cm' => 'nullable|numeric',
            'peso_referencia' => 'nullable|numeric',
        ]);

        $resultadoEstimacion = null;

        if (!empty($datos['peso_estimado'])) {
            $pesoEstimado = (float) $datos['peso_estimado'];
        } else {
            $metodo = $datos['metodo_estimacion'] ?? 'tabla';

            $algoritmo = match ($metodo) {
                'regresion' => new AlgoritmoRegresionLineal(),
                'tabla' => new AlgoritmoTablaReferencia(),
                default => new AlgoritmoYolov8(),
            };

            $estimador = new EstimadorPesoService($algoritmo);
            $resultadoEstimacion = $estimador->estimar($datos);

            $pesoEstimado = $resultadoEstimacion->pesoKg;
        }

        $pesaje = new Pesaje([
            'animal_id' => $datos['animal_id'],
            'peso_estimado' => $pesoEstimado,
            'peso_real' => $datos['peso_real'] ?? null,
            'fecha' => $datos['fecha'],
            'fuente_id' => $datos['fuente_id'] ?? null,
        ]);

        $subject = new PesajeSubject();

        $subject->suscribir(new NotificadorPropietario());
        $subject->suscribir(new RecalculadorICC());
        $subject->suscribir(new WebhookSenasa());
        $subject->suscribir(new AlertaSMS());

        $pesaje = $subject->registrar($pesaje);

        $pesaje->load([
            'animal.raza',
            'animal.finca',
            'fuente'
        ]);

        $respuesta = $pesaje->toArray();

        if ($resultadoEstimacion) {
            $respuesta['estimacion'] = $resultadoEstimacion->toArray();
        }

        return ApiResponse::success(
            'Pesaje registrado correctamente',
            $respuesta,
            201
        );
    }

    public function obtener(int $id): JsonResponse
    {
        $pesaje = Pesaje::with([
                'animal.raza',
                'animal.finca',
                'fuente'
            ])
            ->find($id);

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
        $pesaje = Pesaje::find($id);

        if (!$pesaje) {
            return ApiResponse::error(
                'Pesaje no encontrado',
                [],
                404
            );
        }

        $datos = $request->validate([
            'peso_estimado' => 'required|numeric|min:0',
            'peso_real' => 'nullable|numeric|min:0',
            'fecha' => 'required|date',
            'fuente_id' => 'nullable|exists:fuentes_pesaje,id',
        ]);

        $pesaje->update($datos);

        $pesaje->load([
            'animal.raza',
            'animal.finca',
            'fuente'
        ]);

        return ApiResponse::success(
            'Pesaje actualizado correctamente',
            $pesaje
        );
    }

    public function eliminar(int $id): JsonResponse
    {
        $pesaje = Pesaje::find($id);

        if (!$pesaje) {
            return ApiResponse::error(
                'Pesaje no encontrado',
                [],
                404
            );
        }

        $pesaje->delete();

        return ApiResponse::success(
            'Pesaje eliminado correctamente'
        );
    }

    public function estimarPeso(Request $request): JsonResponse
{
    $datos = $request->validate([
        'imagen' => 'required|image|max:10240',
        'animal_id' => 'required|exists:animales,id',
        'fecha' => 'nullable|date',
        'fuente_id' => 'nullable|exists:fuentes_pesaje,id',
    ]);

    try {
        $imagen = $request->file('imagen');

        if (!$imagen || !$imagen->isValid()) {
            return ApiResponse::error(
                'No se recibió una imagen válida.',
                [],
                422
            );
        }

        return DB::transaction(function () use ($imagen, $datos) {

            $resultadoIA = $this->servicioIA->analizarImagen($imagen);

            $fecha = $datos['fecha'] ?? now()->toDateString();

            $pesaje = Pesaje::create([
                'animal_id' => $datos['animal_id'],
                'peso_estimado' => $resultadoIA['peso_estimado'],
                'peso_real' => null,
                'fecha' => $fecha,
                'fuente_id' => $datos['fuente_id'] ?? 1,
            ]);

            $rutaImagen = $imagen->store(
                'pesajes',
                'public'
            );

            $registroImagen = Imagen::create([
                'pesaje_id' => $pesaje->id,
                'url' => Storage::url($rutaImagen),
                'procesada' => true,
                'fecha' => $fecha,
            ]);

            $pesaje->load([
                'animal.raza',
                'animal.finca',
                'fuente'
            ]);

            return ApiResponse::success(
                'Peso estimado y pesaje guardado correctamente',
                [
                    'estimacion' => $resultadoIA,
                    'pesaje' => $pesaje,
                    'imagen' => $registroImagen,
                ],
                201
            );
        });

    } catch (Throwable $error) {
        return ApiResponse::error(
            $error->getMessage(),
            [],
            500
        );
    }
}
}