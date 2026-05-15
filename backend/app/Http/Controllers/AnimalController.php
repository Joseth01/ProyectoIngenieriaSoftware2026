<?php

namespace App\Http\Controllers;

use App\Services\AnimalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ANTES: llamaba Animal::where(), Animal::findOrFail(), new Brahman() directamente.
 * DESPUÉS: delega toda la lógica a AnimalService, que usa IAnimalRepository (Repository)
 *          e IRazaFactory (Factory) inyectados por el Service Container de Laravel.
 *
 * El controlador solo maneja HTTP — validación de entrada y formato de respuesta.
 */
class AnimalController extends Controller
{
    public function __construct(private readonly AnimalService $animalService) {}

    public function crear(Request $request): JsonResponse
    {
        $request->validate([
            'numero_arete'    => 'required|string|unique:animales,numero_arete',
            'nombre'          => 'required|string|max:255',
            'raza_id'         => 'required|exists:razas,id',
            'nombre_raza'     => 'required|string',
            'fecha_nacimiento' => 'required|date',
            'finca_id'        => 'required|exists:fincas,id',
        ]);

        $animal = $this->animalService->registrar($request->all());

        return response()->json([
            'exito'  => true,
            'mensaje' => 'Animal registrado correctamente',
            'datos'  => $animal,
        ], 201);
    }

    public function listar(): JsonResponse
    {
        $animales = $this->animalService->listarTodos();

        return response()->json([
            'exito' => true,
            'datos' => $animales,
        ]);
    }

    public function buscarPorArete(string $arete): JsonResponse
    {
        $animal = $this->animalService->buscarPorArete($arete);

        if (!$animal) {
            return response()->json(['message' => 'Animal no encontrado'], 404);
        }

        return response()->json(['exito' => true, 'datos' => $animal]);
    }

    public function historial(int $id): JsonResponse
    {
        $animal = $this->animalService->historial($id);

        if (!$animal) {
            return response()->json(['message' => 'Animal no encontrado'], 404);
        }

        return response()->json(['exito' => true, 'datos' => $animal]);
    }

    public function obtener(int $id): JsonResponse
    {
        $animales = $this->animalService->listarTodos();
        $animal   = collect($animales)->firstWhere('id', $id);

        if (!$animal) {
            return response()->json(['message' => 'Animal no encontrado'], 404);
        }

        return response()->json(['exito' => true, 'datos' => $animal]);
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $animal = $this->animalService->historial($id);

        if (!$animal) {
            return response()->json(['message' => 'Animal no encontrado'], 404);
        }

        $animal->update($request->only(['nombre', 'fecha_nacimiento', 'estado']));

        return response()->json([
            'exito'  => true,
            'mensaje' => 'Animal actualizado correctamente',
            'datos'  => $animal,
        ]);
    }

    public function eliminar(int $id): JsonResponse
    {
        $animal = $this->animalService->historial($id);

        if (!$animal) {
            return response()->json(['message' => 'Animal no encontrado'], 404);
        }

        $animal->estado = 'inactivo';
        $this->animalService->historial($id); // refresca
        $animal->save();

        return response()->json(['message' => 'Animal desactivado correctamente']);
    }
}
