<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Animal;
use App\Models\Finca;
use App\Models\Raza;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnimalController extends Controller
{
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
            'numero_arete'      => 'required|string|max:255|unique:animales,numero_arete',
            'nombre'            => 'required|string|max:255',
            'raza_id'           => 'required|exists:razas,id',
            'fecha_nacimiento'  => 'required|date|before_or_equal:today',
            'finca_id'          => 'required|exists:fincas,id',
            'estado'            => 'nullable|in:activo,inactivo',
        ]);

        $fincaPerteneceUsuario = Finca::where('id', $datos['finca_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        if (!$fincaPerteneceUsuario) {
            return ApiResponse::error(
                'La finca seleccionada no pertenece al usuario autenticado',
                [],
                403
            );
        }

        $animal = Animal::create([
            'numero_arete'     => $datos['numero_arete'],
            'nombre'           => $datos['nombre'],
            'raza_id'          => $datos['raza_id'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'finca_id'         => $datos['finca_id'],
            'estado'           => $datos['estado'] ?? 'activo',
        ]);

        $animal->load(['raza', 'finca']);

        return ApiResponse::success(
            'Animal registrado correctamente',
            $animal,
            201
        );
    }

    public function listar(Request $request): JsonResponse
    {
        $animales = Animal::with(['raza', 'finca'])
            ->whereHas('finca', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->orderBy('id', 'desc')
            ->get();

        return ApiResponse::success(
            'Animales obtenidos correctamente',
            $animales
        );
    }

    public function buscarPorArete(Request $request, string $arete): JsonResponse
    {
        $animal = Animal::with(['raza', 'finca'])
            ->where('numero_arete', $arete)
            ->whereHas('finca', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->first();

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

    public function historial(Request $request, int $id): JsonResponse
    {
        $animal = Animal::with([
                'raza',
                'finca',
                'pesajes.fuente',
            ])
            ->where('id', $id)
            ->whereHas('finca', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado o no pertenece al usuario',
                [],
                404
            );
        }

        $animal->pesajes = $animal->pesajes
            ->sortByDesc(function ($pesaje) {
                return $pesaje->fecha . ' ' . $pesaje->created_at . ' ' . $pesaje->id;
            })
            ->values();

        return ApiResponse::success(
            'Historial obtenido correctamente',
            $animal
        );
    }

    public function obtener(Request $request, int $id): JsonResponse
    {
        $animal = Animal::with(['raza', 'finca'])
            ->where('id', $id)
            ->whereHas('finca', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado o no pertenece al usuario',
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
        $animal = Animal::with(['raza', 'finca'])
            ->where('id', $id)
            ->whereHas('finca', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado o no pertenece al usuario',
                [],
                404
            );
        }

        $datos = $request->validate([
            'numero_arete'      => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('animales', 'numero_arete')->ignore($id),
            ],
            'nombre'            => 'sometimes|string|max:255',
            'raza_id'           => 'sometimes|exists:razas,id',
            'fecha_nacimiento'  => 'sometimes|date|before_or_equal:today',
            'estado'            => 'sometimes|in:activo,inactivo',
            'finca_id'          => 'sometimes|exists:fincas,id',
        ]);

        if (isset($datos['finca_id'])) {
            $fincaPerteneceUsuario = Finca::where('id', $datos['finca_id'])
                ->where('user_id', $request->user()->id)
                ->exists();

            if (!$fincaPerteneceUsuario) {
                return ApiResponse::error(
                    'La finca seleccionada no pertenece al usuario autenticado',
                    [],
                    403
                );
            }
        }

        $animal->update($datos);

        $animal->load(['raza', 'finca']);

        return ApiResponse::success(
            'Animal actualizado correctamente',
            $animal
        );
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $animal = Animal::where('id', $id)
            ->whereHas('finca', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$animal) {
            return ApiResponse::error(
                'Animal no encontrado o no pertenece al usuario',
                [],
                404
            );
        }

        $animal->update([
            'estado' => 'inactivo',
        ]);

        return ApiResponse::success(
            'Animal desactivado correctamente'
        );
    }
}