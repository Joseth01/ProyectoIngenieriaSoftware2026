<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Services\ReporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ANTES: Animal::where('finca_id', $id)->with('pesajes')->get() directo aquí.
 * DESPUÉS: delega a ReporteService que usa IAnimalRepository + IRazaFactory.
 */
class ReporteController extends Controller
{
    public function __construct(private readonly ReporteService $reporteService) {}

    public function listar(): JsonResponse
    {
        $reportes = Reporte::all();

        return response()->json(['exito' => true, 'datos' => $reportes]);
    }

    public function obtenerPorUsuario(int $user_id): JsonResponse
    {
        $reportes = Reporte::where('user_id', $user_id)->get();

        return response()->json(['exito' => true, 'datos' => $reportes]);
    }

    /**
     * Genera reporte de animales por finca usando Repository + Factory.
     */
    public function reportePorFinca(int $fincaId): JsonResponse
    {
        $datos = $this->reporteService->reportePorFinca($fincaId);

        return response()->json(['exito' => true, 'datos' => $datos]);
    }

    public function crear(Request $request): JsonResponse
    {
        $datos = $this->validarDatos($request);
        $reporte = Reporte::create($datos);

        return response()->json([
            'exito'  => true,
            'mensaje' => 'Reporte creado correctamente',
            'datos'  => $reporte,
        ], 201);
    }

    public function obtener(int $id): JsonResponse
    {
        $reporte = Reporte::findOrFail($id);

        return response()->json(['exito' => true, 'datos' => $reporte]);
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $reporte = Reporte::findOrFail($id);
        $datos   = $this->validarDatos($request);
        $reporte->update($datos);

        return response()->json([
            'exito'  => true,
            'mensaje' => 'Reporte actualizado correctamente',
            'datos'  => $reporte,
        ]);
    }

    public function eliminar(int $id): JsonResponse
    {
        $reporte = Reporte::findOrFail($id);
        $reporte->delete();

        return response()->json(['exito' => true, 'mensaje' => 'Reporte eliminado correctamente']);
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'user_id'    => 'required|exists:users,id',
            'tipo'       => 'required|string|max:255',
            'archivo_url' => 'nullable|string|url',
            'fecha'      => 'required|date',
        ]);
    }
}
