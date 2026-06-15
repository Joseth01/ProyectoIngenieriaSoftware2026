<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\VeterinarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\AuditoriaService;

class VeterinarioController extends Controller
{
    public function __construct(
        private readonly VeterinarioService $veterinarioService,
        private readonly AuditoriaService $auditoriaService
    ) {}

    private function validarVeterinario(Request $request): ?JsonResponse
    {
        if ($request->user()->rol !== 'veterinario') {
            return ApiResponse::error(
                'No tiene permisos para acceder al módulo veterinario.',
                [],
                403
            );
        }

        if (!$request->user()->activo) {
            return ApiResponse::error(
                'La cuenta está desactivada. Contacte al administrador.',
                [],
                403
            );
        }

        return null;
    }

    public function perfil(Request $request): JsonResponse
    {
        $error = $this->validarVeterinario($request);

        if ($error) {
            return $error;
        }

        $datos = $this->veterinarioService
            ->obtenerPerfil($request->user());

        return ApiResponse::success(
            'Perfil veterinario obtenido correctamente',
            $datos
        );
    }

    public function actualizarPerfil(Request $request): JsonResponse
{
    $error = $this->validarVeterinario($request);

    if ($error) {
        return $error;
    }

    $datos = $request->validate([
        'codigo_colegiado' => 'nullable|string|max:50',
        'telefono_urgencias' => 'nullable|string|max:30',
        'especialidad' => 'nullable|string|max:100',
    ]);

    $perfilActual = $this->veterinarioService
        ->obtenerPerfil($request->user())['perfil_veterinario'];

    $datosAnteriores = [
        'id' => $perfilActual->id,
        'user_id' => $perfilActual->user_id,
        'codigo_colegiado' => $perfilActual->codigo_colegiado,
        'telefono_urgencias' => $perfilActual->telefono_urgencias,
        'especialidad' => $perfilActual->especialidad,
    ];

    $perfil = $this->veterinarioService
        ->actualizarPerfil(
            $request->user(),
            $datos
        );

    $datosNuevos = [
        'id' => $perfil->id,
        'user_id' => $perfil->user_id,
        'codigo_colegiado' => $perfil->codigo_colegiado,
        'telefono_urgencias' => $perfil->telefono_urgencias,
        'especialidad' => $perfil->especialidad,
    ];

    $this->auditoriaService
        ->registrarActualizacionPerfilVeterinario(
            $request->user(),
            $datosAnteriores,
            $datosNuevos,
            $request
        );

    return ApiResponse::success(
        'Perfil veterinario actualizado correctamente',
        $perfil
    );
}

    public function fincas(Request $request): JsonResponse
    {
        $error = $this->validarVeterinario($request);

        if ($error) {
            return $error;
        }

        $fincas = $this->veterinarioService
            ->obtenerFincasAsignadas($request->user());

        return ApiResponse::success(
            'Fincas asignadas obtenidas correctamente',
            $fincas
        );
    }

    public function animales(Request $request): JsonResponse
    {
        $error = $this->validarVeterinario($request);

        if ($error) {
            return $error;
        }

        $animales = $this->veterinarioService
            ->obtenerAnimalesAsignados($request->user());

        return ApiResponse::success(
            'Animales asignados obtenidos correctamente',
            $animales
        );
    }

    public function detalleAnimal(
        Request $request,
        int $id
    ): JsonResponse {
        $error = $this->validarVeterinario($request);

        if ($error) {
            return $error;
        }

        $animal = $this->veterinarioService
            ->obtenerDetalleAnimal(
                $request->user(),
                $id
            );

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado o no asignado al veterinario.',
                [],
                404
            );
        }

        return ApiResponse::success(
            'Detalle del animal obtenido correctamente',
            $animal
        );
    }

}