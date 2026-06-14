<?php

namespace App\Services;

use App\Models\Finca;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FincaService
{
    public function listarPorUsuario(User $usuario): Collection
    {
        return Finca::where('user_id', $usuario->id)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function crearParaUsuario(
        User $usuario,
        array $datos
    ): Finca {
        $datos['user_id'] = $usuario->id;

        return Finca::create($datos);
    }

    public function obtenerPorId(int $id): ?Finca
    {
        return Finca::find($id);
    }

    public function obtenerPorUsuario(
        int $id,
        User $usuario
    ): ?Finca {
        return Finca::where('id', $id)
            ->where('user_id', $usuario->id)
            ->first();
    }

    public function actualizar(
        Finca $finca,
        array $datos
    ): Finca {
        $finca->update($datos);

        return $finca->fresh();
    }

    public function listarPorUsuarioId(int $userId): Collection
    {
        return Finca::where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get();
    }
}