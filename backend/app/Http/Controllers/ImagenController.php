<?php

namespace App\Http\Controllers;

use App\Models\Imagen;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;

class ImagenController extends Controller
{
    public function listar(): JsonResponse
    {
        $imagenes = Imagen::all();

        return ApiResponse::success(
            'Imágenes obtenidas correctamente',
            $imagenes
        );
    }

    public function obtenerPorAnimal(int $animal_id): JsonResponse
    {
        $imagenes = Imagen::where('animal_id', $animal_id)->get();

        return ApiResponse::success(
            'Imágenes del animal obtenidas correctamente',
            $imagenes
        );
    }

    public function crear(Request $request): JsonResponse
    {
        $datos = $this->validarDatos($request);

        $imagen = Imagen::create($datos);

        return ApiResponse::success(
            'Imagen creada correctamente',
            $imagen,
            201
        );
    }

    public function obtener(int $id): JsonResponse
    {
        $imagen = Imagen::find($id);

        if (!$imagen) {
            return ApiResponse::error(
                'Imagen no encontrada',
                [],
                404
            );
        }

        return ApiResponse::success(
            'Imagen obtenida correctamente',
            $imagen
        );
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $imagen = Imagen::find($id);

        if (!$imagen) {
            return ApiResponse::error(
                'Imagen no encontrada',
                [],
                404
            );
        }

        $datos = $this->validarDatos($request);

        $imagen->update($datos);

        return ApiResponse::success(
            'Imagen actualizada correctamente',
            $imagen
        );
    }

    public function eliminar(int $id): JsonResponse
    {
        $imagen = Imagen::find($id);

        if (!$imagen) {
            return ApiResponse::error(
                'Imagen no encontrada',
                [],
                404
            );
        }

        $imagen->delete();

        return ApiResponse::success(
            'Imagen eliminada correctamente'
        );
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'animal_id' => 'required|exists:animales,id',
            'url' => 'required|string|url',
            'procesada' => 'nullable|boolean',
            'fecha' => 'required|date'
        ]);
    }
}