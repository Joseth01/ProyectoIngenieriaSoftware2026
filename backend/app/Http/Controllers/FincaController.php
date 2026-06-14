<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;
use App\Services\FincaService;
use App\Services\AuditoriaService;

class FincaController extends Controller
{
    public function __construct(
        private readonly FincaService $fincaService,
        private readonly AuditoriaService $auditoriaService
    ) {}

    public function listarFincas(Request $request): JsonResponse
    {
        $fincas = $this->fincaService
            ->listarPorUsuario($request->user());

        return ApiResponse::success(
            'Fincas obtenidas correctamente',
            $fincas
        );
    }

    public function crearFinca(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'ubicacion' => 'required|string|max:255',
        ]);

        $finca = $this->fincaService
            ->crearParaUsuario(
                $request->user(),
                $datos
            );

        $this->auditoriaService->registrar(
            accion: 'CREAR_FINCA',
            modulo: 'Fincas',
            descripcion: 'El usuario registró una nueva finca.',
            entidadTipo: 'Finca',
            entidadId: $finca->id,
            datosAnteriores: null,
            datosNuevos: [
                'id' => $finca->id,
                'nombre' => $finca->nombre,
                'ubicacion' => $finca->ubicacion,
                'user_id' => $finca->user_id,
            ],
            usuario: $request->user(),
            request: $request
        );

        return ApiResponse::success(
            'Finca creada correctamente',
            $finca,
            201
        );
    }

    public function obtenerFinca($id): JsonResponse
    {
        $finca = $this->fincaService
            ->obtenerPorId((int) $id);

        if (!$finca) {
            return ApiResponse::error(
                'Finca no encontrada',
                [],
                404
            );
        }

        return ApiResponse::success(
            'Finca obtenida correctamente',
            $finca
        );
    }

    public function actualizarFinca(
        Request $request,
        $id
    ): JsonResponse {
        $finca = $this->fincaService
            ->obtenerPorUsuario(
                (int) $id,
                $request->user()
            );

        if (!$finca) {
            return ApiResponse::error(
                'Finca no encontrada o no pertenece al usuario',
                [],
                404
            );
        }

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'ubicacion' => 'required|string|max:255',
        ]);

        $datosAnteriores = [
            'nombre' => $finca->nombre,
            'ubicacion' => $finca->ubicacion,
        ];

        $fincaActualizada = $this->fincaService
            ->actualizar(
                $finca,
                $datos
            );

        $this->auditoriaService->registrar(
            accion: 'ACTUALIZAR_FINCA',
            modulo: 'Fincas',
            descripcion: 'El usuario actualizó los datos de una finca.',
            entidadTipo: 'Finca',
            entidadId: $fincaActualizada->id,
            datosAnteriores: $datosAnteriores,
            datosNuevos: [
                'nombre' => $fincaActualizada->nombre,
                'ubicacion' => $fincaActualizada->ubicacion,
            ],
            usuario: $request->user(),
            request: $request
        );

        return ApiResponse::success(
            'Finca actualizada correctamente',
            $fincaActualizada
        );
    }

    public function eliminarFinca($id): JsonResponse
    {
        return ApiResponse::error(
            'La eliminación de fincas no está permitida para proteger los datos relacionados.',
            [],
            403
        );
    }

    public function obtenerFincasPorUsuario($user_id): JsonResponse
    {
        $fincas = $this->fincaService
            ->listarPorUsuarioId((int) $user_id);

        return ApiResponse::success(
            'Fincas del usuario obtenidas correctamente',
            $fincas
        );
    }
}