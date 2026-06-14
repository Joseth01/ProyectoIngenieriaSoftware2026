<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Finca;
use App\Models\Raza;
use App\Services\AnimalService;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnimalController extends Controller
{
    /**
     * Formato del arete de identificación (DIIO) de SENASA Costa Rica.
     * Acepta un prefijo opcional de hasta 3 letras (p.ej. "CR"), seguido de
     * al menos 3 dígitos, con grupos adicionales separados por guion o barra.
     * Cubre el formato numérico oficial de SENASA y los formatos usados en
     * las fincas (ej. "CR005", "001-2026", "188000123456").
     */
    private const ARETE_REGEX = 'regex:/^[A-Za-z]{0,3}[0-9]{3,}([-\/][0-9]+)*$/';

    private const ARETE_MENSAJE =
        'El número de arete no tiene un formato válido de SENASA. '
        . 'Debe contener dígitos (prefijo de país y separadores opcionales), '
        . 'ej: CR005, 001-2026 o 188000123456.';

    public function __construct(
        private readonly AnimalService $animalService,
        private readonly AuditoriaService $auditoriaService
    ) {}

    public function razas(): JsonResponse
    {
        $razas = Raza::orderBy('id')->get();

        return ApiResponse::success(
            'Razas obtenidas correctamente',
            $razas
        );
    }

    public function crear(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'numero_arete'      => ['required', 'string', 'max:20', self::ARETE_REGEX, 'unique:animales,numero_arete'],
            'nombre'            => 'required|string|max:255',
            'raza_id'           => 'required|exists:razas,id',
            'fecha_nacimiento'  => 'required|date|before_or_equal:today',
            'finca_id'          => 'required|exists:fincas,id',
            'estado'            => 'nullable|in:activo,inactivo',
        ], [
            'numero_arete.regex' => self::ARETE_MENSAJE,
        ]);

        $fincaPerteneceUsuario = Finca::where('id', $datos['finca_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        if (!$fincaPerteneceUsuario) {
            return ApiResponse::error(
                'La finca seleccionada no pertenece al usuario autenticado',
                [],
                403
            );
        }

        $animal = $this->animalService
            ->registrar($datos);

        $this->auditoriaService->registrar(
            accion: 'CREAR_ANIMAL',
            modulo: 'Animales',
            descripcion: 'El usuario registró un nuevo animal.',
            entidadTipo: 'Animal',
            entidadId: $animal->id,
            datosAnteriores: null,
            datosNuevos: [
                'id' => $animal->id,
                'numero_arete' => $animal->numero_arete,
                'nombre' => $animal->nombre,
                'raza_id' => $animal->raza_id,
                'raza' => $animal->raza?->nombre,
                'fecha_nacimiento' => $animal->fecha_nacimiento,
                'estado' => $animal->estado,
                'finca_id' => $animal->finca_id,
                'finca' => $animal->finca?->nombre,
            ],
            usuario: $request->user(),
            request: $request
        );

        return ApiResponse::success(
            'Animal registrado correctamente',
            $animal,
            201
        );
    }

    public function listar(Request $request): JsonResponse
    {
        $animales = $this->animalService
            ->listarPorUsuario($request->user());

        return ApiResponse::success(
            'Animales obtenidos correctamente',
            $animales
        );
    }

    public function buscarPorArete(Request $request, string $arete): JsonResponse
    {
        $animal = $this->animalService
            ->buscarPorArete(
                $arete,
                $request->user()
            );

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado',
                [],
                404
            );
        }

        return ApiResponse::success(
            'Animal encontrado correctamente',
            $animal
        );
    }

    public function historial(Request $request, int $id): JsonResponse
    {
        $animal = $this->animalService
            ->historialPorUsuario(
                $id,
                $request->user()
            );

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado o no pertenece al usuario',
                [],
                404
            );
        }

        $animal->pesajes = $animal->pesajes
            ->sortByDesc(function ($pesaje) {
                return $pesaje->fecha . ' ' . $pesaje->created_at . ' ' . $pesaje->id;
            })
            ->values();

        return ApiResponse::success(
            'Historial obtenido correctamente',
            $animal
        );
    }

    public function obtener(Request $request, int $id): JsonResponse
    {
        $animal = $this->animalService
            ->obtenerPorUsuario(
                $id,
                $request->user()
            );

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado o no pertenece al usuario',
                [],
                404
            );
        }

        return ApiResponse::success(
            'Animal obtenido correctamente',
            $animal
        );
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $animal = $this->animalService
            ->obtenerPorUsuario(
                $id,
                $request->user()
            );

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado o no pertenece al usuario',
                [],
                404
            );
        }

        $datos = $request->validate([
            'numero_arete'      => [
                'sometimes',
                'string',
                'max:20',
                self::ARETE_REGEX,
                Rule::unique('animales', 'numero_arete')->ignore($id),
            ],
            'nombre'            => 'sometimes|string|max:255',
            'raza_id'           => 'sometimes|exists:razas,id',
            'fecha_nacimiento'  => 'sometimes|date|before_or_equal:today',
            'estado'            => 'sometimes|in:activo,inactivo',
            'finca_id'          => 'sometimes|exists:fincas,id',
        ], [
            'numero_arete.regex' => self::ARETE_MENSAJE,
        ]);

        if (isset($datos['finca_id'])) {
            $fincaPerteneceUsuario = Finca::where('id', $datos['finca_id'])
                ->where('user_id', $request->user()->id)
                ->exists();

            if (!$fincaPerteneceUsuario) {
                return ApiResponse::error(
                    'La finca seleccionada no pertenece al usuario autenticado',
                    [],
                    403
                );
            }
        }

        $datosAnteriores = [
            'numero_arete' => $animal->numero_arete,
            'nombre' => $animal->nombre,
            'raza_id' => $animal->raza_id,
            'raza' => $animal->raza?->nombre,
            'fecha_nacimiento' => $animal->fecha_nacimiento,
            'estado' => $animal->estado,
            'finca_id' => $animal->finca_id,
            'finca' => $animal->finca?->nombre,
        ];

        $animalActualizado = $this->animalService
            ->actualizar(
                $animal,
                $datos
            );

        $accion = 'ACTUALIZAR_ANIMAL';
        $descripcion = 'El usuario actualizó los datos de un animal.';

        if (
            array_key_exists('estado', $datos) &&
            $datosAnteriores['estado'] !== $animalActualizado->estado &&
            $animalActualizado->estado === 'inactivo'
        ) {
            $accion = 'DESACTIVAR_ANIMAL';
            $descripcion = 'El usuario desactivó un animal.';
        }

        $this->auditoriaService->registrar(
            accion: $accion,
            modulo: 'Animales',
            descripcion: $descripcion,
            entidadTipo: 'Animal',
            entidadId: $animalActualizado->id,
            datosAnteriores: $datosAnteriores,
            datosNuevos: [
                'numero_arete' => $animalActualizado->numero_arete,
                'nombre' => $animalActualizado->nombre,
                'raza_id' => $animalActualizado->raza_id,
                'raza' => $animalActualizado->raza?->nombre,
                'fecha_nacimiento' => $animalActualizado->fecha_nacimiento,
                'estado' => $animalActualizado->estado,
                'finca_id' => $animalActualizado->finca_id,
                'finca' => $animalActualizado->finca?->nombre,
            ],
            usuario: $request->user(),
            request: $request
        );

        return ApiResponse::success(
            'Animal actualizado correctamente',
            $animalActualizado
        );
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $animal = $this->animalService
            ->obtenerPorUsuario(
                $id,
                $request->user()
            );

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado o no pertenece al usuario',
                [],
                404
            );
        }

        $datosAnteriores = [
            'numero_arete' => $animal->numero_arete,
            'nombre' => $animal->nombre,
            'estado' => $animal->estado,
            'finca_id' => $animal->finca_id,
            'finca' => $animal->finca?->nombre,
        ];

        $animalActualizado = $this->animalService
            ->desactivar($animal);

        $this->auditoriaService->registrar(
            accion: 'DESACTIVAR_ANIMAL',
            modulo: 'Animales',
            descripcion: 'El usuario desactivó un animal.',
            entidadTipo: 'Animal',
            entidadId: $animalActualizado->id,
            datosAnteriores: $datosAnteriores,
            datosNuevos: [
                'numero_arete' => $animalActualizado->numero_arete,
                'nombre' => $animalActualizado->nombre,
                'estado' => $animalActualizado->estado,
                'finca_id' => $animalActualizado->finca_id,
                'finca' => $animalActualizado->finca?->nombre,
            ],
            usuario: $request->user(),
            request: $request
        );

        return ApiResponse::success(
            'Animal desactivado correctamente',
            $animalActualizado
        );
    }
}