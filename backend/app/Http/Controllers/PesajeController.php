<?php

namespace App\Http\Controllers;

use App\Domain\Pesajes\PesajeSubject;
use App\Estimacion\AlgoritmoRegresionLineal;
use App\Estimacion\AlgoritmoTablaReferencia;
use App\Estimacion\AlgoritmoYolov8;
use App\Helpers\ApiResponse;
use App\Models\Animal;
use App\Models\Imagen;
use App\Models\Pesaje;
use App\Observers\AlertaSMS;
use App\Observers\NotificadorPropietario;
use App\Observers\RecalculadorICC;
use App\Observers\WebhookSenasa;
use App\Services\AuditoriaService;
use App\Services\EstimadorPesoService;
use App\Services\ServicioIA;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PesajeController extends Controller
{
    private const PESO_MINIMO = 50;
    private const PESO_MAXIMO = 1200;

    private const FUENTE_IA = 1;
    private const FUENTE_BASCULA = 2;
    private const FUENTE_MANUAL = 3;

    public function __construct(
        private readonly ServicioIA $servicioIA,
        private readonly AuditoriaService $auditoriaService
    ) {}

    public function listar(Request $request): JsonResponse
    {
        $pesajes = Pesaje::with([
                'animal.raza',
                'animal.finca',
                'fuente',
            ])
            ->whereHas('animal.finca', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return ApiResponse::success(
            'Pesajes obtenidos correctamente',
            $pesajes
        );
    }

    public function obtenerPorAnimal(Request $request, int $animal_id): JsonResponse
    {
        if (!$this->animalPerteneceAlUsuario($request, $animal_id)) {
            return ApiResponse::error(
                'Animal no encontrado o no pertenece al usuario',
                [],
                404
            );
        }

        $pesajes = Pesaje::with([
                'animal.raza',
                'animal.finca',
                'fuente',
            ])
            ->where('animal_id', $animal_id)
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
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
            'fecha' => 'required|date|before_or_equal:today',
            'fuente_id' => 'nullable|exists:fuentes_pesaje,id',

            'peso_estimado' => 'nullable|numeric|min:' . self::PESO_MINIMO . '|max:' . self::PESO_MAXIMO,
            'peso_real' => 'nullable|numeric|min:' . self::PESO_MINIMO . '|max:' . self::PESO_MAXIMO,

            'metodo_estimacion' => 'nullable|in:yolov8,regresion,tabla',

            'raza' => 'nullable|string',
            'edad_meses' => 'nullable|integer|min:1',
            'largo_corporal_cm' => 'nullable|numeric',
            'perimetro_toracico_cm' => 'nullable|numeric',
            'peso_referencia' => 'nullable|numeric',
        ]);

        if (!$this->animalPerteneceAlUsuario($request, (int) $datos['animal_id'])) {
            return ApiResponse::error(
                'Animal no encontrado o no pertenece al usuario',
                [],
                404
            );
        }

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

        if (!$this->pesoEnRango($pesoEstimado)) {
            return ApiResponse::error(
                'El peso estimado debe estar entre ' . self::PESO_MINIMO . ' kg y ' . self::PESO_MAXIMO . ' kg',
                [],
                422
            );
        }

        $pesaje = new Pesaje([
            'animal_id' => $datos['animal_id'],
            'peso_estimado' => $pesoEstimado,
            'peso_real' => $datos['peso_real'] ?? null,
            'fecha' => $datos['fecha'],
            'fuente_id' => $datos['fuente_id'] ?? self::FUENTE_MANUAL,
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
            'fuente',
        ]);

        $datosAuditoria = $this->datosPesajeParaAuditoria($pesaje);

        if ($resultadoEstimacion) {
            $datosAuditoria['estimacion'] = $resultadoEstimacion->toArray();
        }

        $this->auditoriaService->registrar(
            accion: 'CREAR_PESAJE',
            modulo: 'Pesajes',
            descripcion: 'El usuario registró un nuevo pesaje.',
            entidadTipo: 'Pesaje',
            entidadId: $pesaje->id,
            datosAnteriores: null,
            datosNuevos: $datosAuditoria,
            usuario: $request->user(),
            request: $request
        );

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

    public function obtener(Request $request, int $id): JsonResponse
    {
        $pesaje = Pesaje::with([
                'animal.raza',
                'animal.finca',
                'fuente',
            ])
            ->where('id', $id)
            ->whereHas('animal.finca', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$pesaje) {
            return ApiResponse::error(
                'Pesaje no encontrado o no pertenece al usuario',
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
        $pesaje = Pesaje::with(['animal.finca'])
            ->where('id', $id)
            ->whereHas('animal.finca', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$pesaje) {
            return ApiResponse::error(
                'Pesaje no encontrado o no pertenece al usuario',
                [],
                404
            );
        }

        $datos = $request->validate([
            'peso_estimado' => 'required|numeric|min:' . self::PESO_MINIMO . '|max:' . self::PESO_MAXIMO,
            'peso_real' => 'nullable|numeric|min:' . self::PESO_MINIMO . '|max:' . self::PESO_MAXIMO,
            'fecha' => 'required|date|before_or_equal:today',
            'fuente_id' => 'nullable|exists:fuentes_pesaje,id',
        ]);

        $pesaje->update([
            'peso_estimado' => $datos['peso_estimado'],
            'peso_real' => $datos['peso_real'] ?? null,
            'fecha' => $datos['fecha'],
            'fuente_id' => $datos['fuente_id'] ?? $pesaje->fuente_id ?? self::FUENTE_MANUAL,
        ]);

        $pesaje->load([
            'animal.raza',
            'animal.finca',
            'fuente',
        ]);

        return ApiResponse::success(
            'Pesaje actualizado correctamente',
            $pesaje->fresh([
                'animal.raza',
                'animal.finca',
                'fuente',
            ])
        );
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $pesaje = Pesaje::where('id', $id)
            ->whereHas('animal.finca', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$pesaje) {
            return ApiResponse::error(
                'Pesaje no encontrado o no pertenece al usuario',
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
                'error' => $mensaje,
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
        $datos = $request->validate([
            'animal_id' => 'required|integer|exists:animales,id',
            'peso_estimado' => 'required|numeric|min:' . self::PESO_MINIMO . '|max:' . self::PESO_MAXIMO,
            'peso_real' => 'nullable|numeric|min:' . self::PESO_MINIMO . '|max:' . self::PESO_MAXIMO,
            'fecha' => 'required|date|before_or_equal:today',
            'imagen' => 'nullable|image|max:10240',
            'fuente_id' => 'nullable|integer|exists:fuentes_pesaje,id',
        ]);

        if (!$this->animalPerteneceAlUsuario($request, (int) $datos['animal_id'])) {
            return ApiResponse::error(
                'Animal no encontrado o no pertenece al usuario',
                [],
                404
            );
        }

        try {
            return DB::transaction(function () use ($request, $datos) {

                $pesaje = Pesaje::create([
                    'animal_id' => $datos['animal_id'],
                    'peso_estimado' => $datos['peso_estimado'],
                    'peso_real' => $datos['peso_real'] ?? null,
                    'fecha' => $datos['fecha'],
                    'fuente_id' => $datos['fuente_id'] ?? self::FUENTE_IA,
                ]);

                $registroImagen = null;

                if ($request->hasFile('imagen')) {
                    $rutaImagen = $request->file('imagen')->store('pesajes', 'public');

                    $registroImagen = Imagen::create([
                        'pesaje_id' => $pesaje->id,
                        'url' => Storage::url($rutaImagen),
                        'procesada' => true,
                        'fecha' => $datos['fecha'],
                    ]);
                }

                $pesaje->load([
                    'animal.raza',
                    'animal.finca',
                    'fuente',
                ]);

                $datosAuditoria = $this->datosPesajeParaAuditoria($pesaje);

                if ($registroImagen) {
                    $datosAuditoria['imagen'] = [
                        'id' => $registroImagen->id,
                        'url' => $registroImagen->url,
                        'procesada' => $registroImagen->procesada,
                        'fecha' => $registroImagen->fecha,
                    ];
                }

                $this->auditoriaService->registrar(
                    accion: 'CREAR_PESAJE_IA',
                    modulo: 'Pesajes',
                    descripcion: 'El usuario guardó un pesaje generado con IA.',
                    entidadTipo: 'Pesaje',
                    entidadId: $pesaje->id,
                    datosAnteriores: null,
                    datosNuevos: $datosAuditoria,
                    usuario: $request->user(),
                    request: $request
                );

                return ApiResponse::success(
                    'Pesaje guardado correctamente',
                    [
                        'pesaje' => $pesaje,
                        'imagen' => $registroImagen,
                    ],
                    201
                );
            });

        } catch (\Throwable $error) {
            Log::error('[confirmarIA] Error al guardar pesaje IA', [
                'error' => $error->getMessage(),
            ]);

            return ApiResponse::error(
                $error->getMessage() ?: 'No se pudo guardar el pesaje.',
                [],
                500
            );
        }
    }

    private function animalPerteneceAlUsuario(Request $request, int $animalId): bool
    {
        return Animal::where('id', $animalId)
            ->whereHas('finca', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->exists();
    }

    private function pesoEnRango(float|int $peso): bool
    {
        return $peso >= self::PESO_MINIMO && $peso <= self::PESO_MAXIMO;
    }

    private function datosPesajeParaAuditoria(Pesaje $pesaje): array
    {
        return [
            'id' => $pesaje->id,
            'animal_id' => $pesaje->animal_id,
            'animal' => [
                'id' => $pesaje->animal?->id,
                'nombre' => $pesaje->animal?->nombre,
                'numero_arete' => $pesaje->animal?->numero_arete,
                'raza' => $pesaje->animal?->raza?->nombre,
                'finca' => $pesaje->animal?->finca?->nombre,
            ],
            'peso_estimado' => $pesaje->peso_estimado,
            'peso_real' => $pesaje->peso_real,
            'fecha' => $pesaje->fecha,
            'fuente_id' => $pesaje->fuente_id,
            'fuente' => $pesaje->fuente?->nombre,
        ];
    }
}