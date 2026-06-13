<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;
use App\Models\Finca;

class FincaController extends Controller
{
    public function listarFincas(Request $request): JsonResponse
    {
    $fincas = Finca::where('user_id', $request->user()->id)->get();

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

    $datos['user_id'] = $request->user()->id;

    $finca = Finca::create($datos);

    return ApiResponse::success(
        'Finca creada correctamente',
        $finca,
        201
    );
    }

    public function obtenerFinca($id): JsonResponse
    {
    $finca = Finca::find($id);

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

    public function actualizarFinca(Request $request, $id): JsonResponse
    {
    $finca = Finca::where('id', $id)
        ->where('user_id', $request->user()->id)
        ->first();

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

    $finca->update($datos);

    return ApiResponse::success(
        'Finca actualizada correctamente',
        $finca->fresh()
    );
    }

    public function eliminarFinca($id): JsonResponse
    {
    $finca = Finca::find($id);

    if (!$finca) {
        return ApiResponse::error(
            'Finca no encontrada',
            [],
            404
        );
    }

    $finca->delete();

    return ApiResponse::success(
        'Finca eliminada correctamente'
    );
    }

  public function obtenerFincasPorUsuario($user_id): JsonResponse
    {
    $fincas = Finca::where('user_id', $user_id)->get();

    return ApiResponse::success(
        'Fincas del usuario obtenidas correctamente',
        $fincas
    );
    }
}