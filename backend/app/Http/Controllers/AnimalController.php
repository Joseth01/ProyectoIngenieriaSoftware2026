<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Raza;
use App\Services\AnimalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function __construct(
        private readonly AnimalService $animalService
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
            'numero_arete'      => 'required|string|unique:animales,numero_arete',
            'nombre'            => 'required|string|max:255',
            'raza_id'           => 'required|exists:razas,id',
            'fecha_nacimiento'  => 'required|date',
            'finca_id'          => 'required|exists:fincas,id',
        ]);

        $animal = $this->animalService->registrar($datos);

        return ApiResponse::success(
            'Animal registrado correctamente',
            $animal,
            201
        );
    }

    public function listar(): JsonResponse
    {
        $animales = $this->animalService->listarTodos();

        return ApiResponse::success(
            'Animales obtenidos correctamente',
            $animales
        );
    }

    public function buscarPorArete(string $arete): JsonResponse
    {
        $animal = $this->animalService->buscarPorArete($arete);

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

    public function historial(int $id): JsonResponse
    {
        $animal = $this->animalService->historial($id);

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado',
                [],
                404
            );
        }

        return ApiResponse::success(
            'Historial obtenido correctamente',
            $animal
        );
    }

    public function obtener(int $id): JsonResponse
    {
        $animal = $this->animalService->obtenerPorId($id);

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado',
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
        $animal = $this->animalService->obtenerPorId($id);

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado',
                [],
                404
            );
        }

        $datos = $request->validate([
            'numero_arete'      => 'sometimes|string|unique:animales,numero_arete,' . $id,
            'nombre'            => 'sometimes|string|max:255',
            'raza_id'           => 'sometimes|exists:razas,id',
            'fecha_nacimiento'  => 'sometimes|date',
            'estado'            => 'sometimes|string',
            'finca_id'          => 'sometimes|exists:fincas,id',
        ]);

        $animal = $this->animalService->actualizar($animal, $datos);

        return ApiResponse::success(
            'Animal actualizado correctamente',
            $animal
        );
    }

    public function eliminar(int $id): JsonResponse
    {
        $animal = $this->animalService->obtenerPorId($id);

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado',
                [],
                404
            );
        }

        $this->animalService->desactivar($animal);

        return ApiResponse::success(
            'Animal desactivado correctamente'
        );
    }
}