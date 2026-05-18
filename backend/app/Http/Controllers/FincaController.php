<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;
use App\Models\Finca;

class FincaController extends Controller
{
    public function listarFincas(): JsonResponse
    {
    $fincas = Finca::all();

    return ApiResponse::success(
        'Fincas obtenidas correctamente',
        $fincas
    );
    }

    public function crearFinca(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'ubicacion' => 'required|string',
            'user_id' => 'required|exists:users,id'
        ]);

        $finca = Finca::create($request->all());

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
    $finca = Finca::find($id);

    if (!$finca) {
        return ApiResponse::error(
            'Finca no encontrada',
            [],
            404
        );
    }

    $finca->update($request->all());

    return ApiResponse::success(
        'Finca actualizada correctamente',
        $finca
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