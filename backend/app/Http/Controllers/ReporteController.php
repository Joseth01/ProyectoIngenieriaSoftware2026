<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Services\ReporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

class ReporteController extends Controller
{
    public function __construct(private readonly ReporteService $reporteService) {}

    public function listar(): JsonResponse
    {
        $reportes = Reporte::all();

        return ApiResponse::success(
            'Reportes obtenidos correctamente',
            $reportes
        );
    }

    public function obtenerPorUsuario(int $user_id): JsonResponse
    {
        $reportes = Reporte::where('user_id', $user_id)->get();

        return ApiResponse::success(
            'Reportes del usuario obtenidos correctamente',
            $reportes
        );
    }

    public function reportePorFinca(int $fincaId): JsonResponse
    {
        $datos = $this->reporteService->reportePorFinca($fincaId);

        return ApiResponse::success(
            'Reporte por finca generado correctamente',
            $datos
        );
    }

    public function crear(Request $request): JsonResponse
    {
        $datos = $this->validarDatos($request);
        $reporte = Reporte::create($datos);

        return ApiResponse::success(
            'Reporte creado correctamente',
            $reporte,
            201
        );
    }

    public function obtener(int $id): JsonResponse
    {
        $reporte = Reporte::find($id);

        if (!$reporte) {
            return ApiResponse::error(
                'Reporte no encontrado',
                [],
                404
            );
        }

        return ApiResponse::success(
            'Reporte obtenido correctamente',
            $reporte
        );
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $reporte = Reporte::find($id);

        if (!$reporte) {
            return ApiResponse::error(
                'Reporte no encontrado',
                [],
                404
            );
        }

        $datos = $this->validarDatos($request);
        $reporte->update($datos);

        return ApiResponse::success(
            'Reporte actualizado correctamente',
            $reporte
        );
    }

    public function eliminar(int $id): JsonResponse
    {
        $reporte = Reporte::find($id);

        if (!$reporte) {
            return ApiResponse::error(
                'Reporte no encontrado',
                [],
                404
            );
        }

        $reporte->delete();

        return ApiResponse::success(
            'Reporte eliminado correctamente'
        );
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'user_id'     => 'required|exists:users,id',
            'tipo'        => 'required|string|max:255',
            'archivo_url' => 'nullable|string|url',
            'fecha'       => 'required|date',
        ]);
    }
}