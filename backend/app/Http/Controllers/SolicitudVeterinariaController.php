<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\AuditoriaService;
use App\Services\SolicitudVeterinariaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitudVeterinariaController extends Controller
{
    public function __construct(
        private readonly SolicitudVeterinariaService $solicitudVeterinariaService,
        private readonly AuditoriaService $auditoriaService
    ) {}

    public function veterinariosDisponibles(Request $request): JsonResponse
    {
        if ($request->user()->rol !== 'ganadero') {
            return ApiResponse::error(
                'Solo los ganaderos pueden consultar veterinarios disponibles.',
                [],
                403
            );
        }

        $veterinarios = $this->solicitudVeterinariaService
            ->listarVeterinariosDisponibles();

        return ApiResponse::success(
            'Veterinarios disponibles obtenidos correctamente',
            $veterinarios
        );
    }

    public function crearSolicitud(Request $request): JsonResponse
    {
        if ($request->user()->rol !== 'ganadero') {
            return ApiResponse::error(
                'Solo los ganaderos pueden crear solicitudes veterinarias.',
                [],
                403
            );
        }

        $datos = $request->validate([
            'animal_id' => 'required|integer|exists:animales,id',
            'veterinario_id' => 'required|integer|exists:users,id',
            'motivo' => 'required|string|min:5|max:1000',
        ]);

        $solicitud = $this->solicitudVeterinariaService
            ->crearSolicitudGanadero(
                $request->user(),
                (int) $datos['animal_id'],
                (int) $datos['veterinario_id'],
                $datos['motivo']
            );

        $this->auditoriaService
            ->registrarCreacionSolicitudVeterinaria(
                $request->user(),
                $solicitud,
                $request
            );

        return ApiResponse::success(
            'Solicitud veterinaria creada correctamente',
            $solicitud,
            201
        );
    }

    public function misSolicitudesGanadero(Request $request): JsonResponse
    {
        if ($request->user()->rol !== 'ganadero') {
            return ApiResponse::error(
                'Solo los ganaderos pueden consultar sus solicitudes veterinarias.',
                [],
                403
            );
        }

        $solicitudes = $this->solicitudVeterinariaService
            ->listarSolicitudesDelGanadero($request->user());

        return ApiResponse::success(
            'Solicitudes veterinarias del ganadero obtenidas correctamente',
            $solicitudes
        );
    }

    public function misSolicitudesVeterinario(Request $request): JsonResponse
    {
        if ($request->user()->rol !== 'veterinario') {
            return ApiResponse::error(
                'Solo los veterinarios pueden consultar sus solicitudes.',
                [],
                403
            );
        }

        $solicitudes = $this->solicitudVeterinariaService
            ->listarSolicitudesDelVeterinario($request->user());

        return ApiResponse::success(
            'Solicitudes veterinarias del veterinario obtenidas correctamente',
            $solicitudes
        );
    }

    public function responderSolicitud(
        Request $request,
        int $id
    ): JsonResponse {
        if ($request->user()->rol !== 'veterinario') {
            return ApiResponse::error(
                'Solo los veterinarios pueden responder solicitudes.',
                [],
                403
            );
        }

        $datos = $request->validate([
            'estado' => 'required|string|in:en_revision,atendida,rechazada',
            'respuesta_veterinario' => 'nullable|string|max:1500',
        ]);

        $solicitudAnterior = $this->solicitudVeterinariaService
            ->obtenerSolicitudDelVeterinario(
                $request->user(),
                $id
            );

        if (!$solicitudAnterior) {
            return ApiResponse::error(
                'Solicitud veterinaria no encontrada o no asignada a este veterinario.',
                [],
                404
            );
        }

        $datosAnteriores = [
            'id' => $solicitudAnterior->id,
            'estado' => $solicitudAnterior->estado,
            'respuesta_veterinario' => $solicitudAnterior->respuesta_veterinario,
            'fecha_atencion' => $solicitudAnterior->fecha_atencion,
        ];

        $solicitud = $this->solicitudVeterinariaService
            ->responderSolicitudVeterinario(
                $request->user(),
                $id,
                $datos['estado'],
                $datos['respuesta_veterinario'] ?? null
            );

        $this->auditoriaService
            ->registrarRespuestaSolicitudVeterinaria(
                $request->user(),
                $solicitud,
                $datosAnteriores,
                $request
            );

        return ApiResponse::success(
            'Solicitud veterinaria actualizada correctamente',
            $solicitud
        );
    }
    public function obtenerSolicitudDelVeterinario(
    User $veterinario,
    int $solicitudId
): ?SolicitudVeterinaria {
    return SolicitudVeterinaria::where('id', $solicitudId)
        ->where('veterinario_id', $veterinario->id)
        ->first();
}
}