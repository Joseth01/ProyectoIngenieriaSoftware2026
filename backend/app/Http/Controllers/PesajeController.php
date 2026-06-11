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
        // El microservicio en Render puede tardar hasta 60 s en despertar (free tier).
        // Aumentamos el límite de ejecución solo para este endpoint.
        set_time_limit(120);

        $request->validate([
            'imagen'      => 'required|image|max:10240',
            'raza'        => 'nullable|string|max:50',
            'edad_meses'  => 'nullable|integer|min:0',
        ]);

        try {
            $rutaRelativa = $request->file('imagen')->store('pesajes');
            $raza         = $request->input('raza', 'brahman');
            $edadMeses    = (int) $request->input('edad_meses', 0);

            $resultado = $this->servicioIA->analizarImagen(
                $rutaRelativa,
                $raza,
                $edadMeses
            );

            return ApiResponse::success('Peso estimado correctamente', $resultado);

        } catch (\Throwable $error) {
            // Distinguir errores de validación de imagen (422) de errores del servidor (500)
            $esErrorImagen = str_contains($error->getMessage(), 'bovino')
                          || str_contains($error->getMessage(), 'imagen')
                          || str_contains($error->getMessage(), 'IA')
                          || str_contains($error->getMessage(), 'animal');

            return ApiResponse::error(
                'No se recibió una imagen válida.',
                [],
                $esErrorImagen ? 422 : 500
            );
        }
    }

    public function confirmarIA(Request $request): JsonResponse
    {
        $request->validate([
            'animal_id'     => 'required|integer|exists:animales,id',
            'peso_estimado' => 'required|numeric|min:1',
            'peso_real'     => 'nullable|numeric|min:1',
            'fecha'         => 'required|date',
            'imagen'        => 'nullable|image|max:10240',
            'fuente_id'     => 'nullable|integer|exists:fuentes_pesaje,id',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                $pesaje = Pesaje::create([
                    'animal_id'     => $request->integer('animal_id'),
                    'peso_estimado' => $request->input('peso_estimado'),
                    'peso_real'     => $request->input('peso_real'),
                    'fecha'         => $request->input('fecha'),
                    'fuente_id'     => $request->input('fuente_id', 2),
                ]);

                $registroImagen = null;

                if ($request->hasFile('imagen')) {
                    $rutaImagen = $request->file('imagen')->store('pesajes', 'public');
                    $registroImagen = Imagen::create([
                        'pesaje_id' => $pesaje->id,
                        'url'       => Storage::url($rutaImagen),
                        'procesada' => true,
                        'fecha'     => $request->input('fecha'),
                    ]);
                }

                $pesaje->load(['animal.raza', 'animal.finca', 'fuente']);

                return ApiResponse::success(
                    'Pesaje guardado correctamente',
                    [
                        'pesaje' => $pesaje,
                        'imagen' => $registroImagen,
                    ],
                    201
                );
            });

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), [], 500);
        }
    }
}