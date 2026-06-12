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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Imagen;

class PesajeController extends Controller
{
    private const PESO_MINIMO_KG = 50;
    private const PESO_MAXIMO_KG = 1200;

    private const FUENTE_IA = 1;
    private const FUENTE_BASCULA = 2;
    private const FUENTE_MANUAL = 3;

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

            'peso_estimado' => [
                'nullable',
                'numeric',
                'min:' . self::PESO_MINIMO_KG,
                'max:' . self::PESO_MAXIMO_KG,
            ],

            'peso_real' => [
                'nullable',
                'numeric',
                'min:' . self::PESO_MINIMO_KG,
                'max:' . self::PESO_MAXIMO_KG,
            ],

            'metodo_estimacion' => 'nullable|in:yolov8,regresion,tabla',

            'raza' => 'nullable|string',
            'edad_meses' => 'nullable|integer|min:1',
            'largo_corporal_cm' => 'nullable|numeric',
            'perimetro_toracico_cm' => 'nullable|numeric',
            'peso_referencia' => 'nullable|numeric',
        ], [
            'peso_estimado.numeric' => 'El peso registrado debe ser un número válido.',
            'peso_estimado.min' => 'El peso registrado debe ser mayor o igual a 50 kg.',
            'peso_estimado.max' => 'El peso registrado no puede superar los 1200 kg.',

            'peso_real.numeric' => 'El peso real debe ser un número válido.',
            'peso_real.min' => 'El peso real debe ser mayor o igual a 50 kg.',
            'peso_real.max' => 'El peso real no puede superar los 1200 kg.',
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

            $pesoEstimado = (float) $resultadoEstimacion->pesoKg;
        }

        if (
            $pesoEstimado < self::PESO_MINIMO_KG ||
            $pesoEstimado > self::PESO_MAXIMO_KG
        ) {
            return ApiResponse::error(
                'El peso registrado debe estar entre 50 kg y 1200 kg.',
                [],
                422
            );
        }

        $pesoReal = isset($datos['peso_real'])
            ? (float) $datos['peso_real']
            : null;

        if (
            $pesoReal !== null &&
            (
                $pesoReal < self::PESO_MINIMO_KG ||
                $pesoReal > self::PESO_MAXIMO_KG
            )
        ) {
            return ApiResponse::error(
                'El peso real debe estar entre 50 kg y 1200 kg.',
                [],
                422
            );
        }

        $fuenteId = $datos['fuente_id'] ?? self::FUENTE_MANUAL;

        $pesaje = new Pesaje([
            'animal_id' => $datos['animal_id'],
            'peso_estimado' => $pesoEstimado,
            'peso_real' => $pesoReal,
            'fecha' => $datos['fecha'],
            'fuente_id' => $fuenteId,
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
            'peso_estimado' => [
                'required',
                'numeric',
                'min:' . self::PESO_MINIMO_KG,
                'max:' . self::PESO_MAXIMO_KG,
            ],

            'peso_real' => [
                'nullable',
                'numeric',
                'min:' . self::PESO_MINIMO_KG,
                'max:' . self::PESO_MAXIMO_KG,
            ],

            'fecha' => 'required|date',
            'fuente_id' => 'nullable|exists:fuentes_pesaje,id',
        ], [
            'peso_estimado.required' => 'El peso registrado es obligatorio.',
            'peso_estimado.numeric' => 'El peso registrado debe ser un número válido.',
            'peso_estimado.min' => 'El peso registrado debe ser mayor o igual a 50 kg.',
            'peso_estimado.max' => 'El peso registrado no puede superar los 1200 kg.',

            'peso_real.numeric' => 'El peso real debe ser un número válido.',
            'peso_real.min' => 'El peso real debe ser mayor o igual a 50 kg.',
            'peso_real.max' => 'El peso real no puede superar los 1200 kg.',
        ]);

        $datos['fuente_id'] = $datos['fuente_id']
            ?? $pesaje->fuente_id
            ?? self::FUENTE_MANUAL;

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
        set_time_limit(120);

        $request->validate([
            'imagen' => 'required|image|max:10240',
            'raza' => 'nullable|string|max:50',
            'edad_meses' => 'nullable|integer|min:0',
        ]);

        try {
            $rutaRelativa = $request->file('imagen')->store('pesajes');
            $raza = $request->input('raza', 'brahman');
            $edadMeses = (int) $request->input('edad_meses', 0);

            $resultado = $this->servicioIA->analizarImagen(
                $rutaRelativa,
                $raza,
                $edadMeses
            );

            return ApiResponse::success(
                'Peso estimado correctamente',
                $resultado
            );

        } catch (\Throwable $error) {
            $mensaje = $error->getMessage();

            $esErrorImagen = str_contains($mensaje, 'bovino')
                || str_contains($mensaje, 'imagen')
                || str_contains($mensaje, 'IA')
                || str_contains($mensaje, 'animal')
                || str_contains($mensaje, 'válida')
                || str_contains($mensaje, 'detectado')
                || str_contains($mensaje, 'peso');

            Log::warning('[estimarPeso] Error al analizar imagen', [
                'error' => $mensaje
            ]);

            return ApiResponse::error(
                $mensaje ?: 'No se pudo procesar la imagen.',
                [],
                $esErrorImagen ? 422 : 500
            );
        }
    }

    public function confirmarIA(Request $request): JsonResponse
    {
        $request->validate([
            'animal_id' => 'required|integer|exists:animales,id',

            'peso_estimado' => [
                'required',
                'numeric',
                'min:' . self::PESO_MINIMO_KG,
                'max:' . self::PESO_MAXIMO_KG,
            ],

            'peso_real' => [
                'nullable',
                'numeric',
                'min:' . self::PESO_MINIMO_KG,
                'max:' . self::PESO_MAXIMO_KG,
            ],

            'fecha' => 'required|date',
            'imagen' => 'nullable|image|max:10240',
            'fuente_id' => 'nullable|integer|exists:fuentes_pesaje,id',
        ], [
            'peso_estimado.required' => 'El peso estimado es obligatorio.',
            'peso_estimado.numeric' => 'El peso estimado debe ser un número válido.',
            'peso_estimado.min' => 'El peso estimado debe ser mayor o igual a 50 kg.',
            'peso_estimado.max' => 'El peso estimado no puede superar los 1200 kg.',

            'peso_real.numeric' => 'El peso real debe ser un número válido.',
            'peso_real.min' => 'El peso real debe ser mayor o igual a 50 kg.',
            'peso_real.max' => 'El peso real no puede superar los 1200 kg.',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                $pesaje = Pesaje::create([
                    'animal_id' => $request->integer('animal_id'),
                    'peso_estimado' => $request->input('peso_estimado'),
                    'peso_real' => $request->input('peso_real'),
                    'fecha' => $request->input('fecha'),
                    'fuente_id' => $request->input('fuente_id', self::FUENTE_IA),
                ]);

                $registroImagen = null;

                if ($request->hasFile('imagen')) {
                    $rutaImagen = $request->file('imagen')->store('pesajes', 'public');

                    $registroImagen = Imagen::create([
                        'pesaje_id' => $pesaje->id,
                        'url' => Storage::url($rutaImagen),
                        'procesada' => true,
                        'fecha' => $request->input('fecha'),
                    ]);
                }

                $pesaje->load([
                    'animal.raza',
                    'animal.finca',
                    'fuente'
                ]);

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
            return ApiResponse::error(
                $e->getMessage(),
                [],
                500
            );
        }
    }
}