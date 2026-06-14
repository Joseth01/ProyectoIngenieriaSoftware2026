<?php

namespace App\Services;

use App\Models\User;
use App\Models\Finca;
use App\Models\Animal;
use Illuminate\Database\Eloquent\Collection;

class UsuarioService
{
    public function obtenerPerfilCompleto(
        User $usuario
    ): array {

        $fincas = Finca::where(
            'user_id',
            $usuario->id
        )->get();

        $fincaIds = $fincas->pluck('id');

        $totalAnimales = Animal::whereIn(
            'finca_id',
            $fincaIds
        )->count();

        return [
            'usuario' => $usuario,
            'fincas' => $fincas,
            'total_animales' => $totalAnimales,
            'total_fincas' => $fincas->count()
        ];
    }

    public function listarUsuariosAdmin(): Collection
    {
        return User::select(
            'id',
            'name',
            'email',
            'rol',
            'activo',
            'created_at',
            'updated_at'
        )
            ->orderBy('id', 'asc')
            ->get();
    }

    public function cambiarEstadoUsuarioAdmin(
        int $usuarioId
    ): array {
        $usuario = User::findOrFail($usuarioId);

        $estadoAnterior = (bool) $usuario->activo;

        $usuario->activo = !$usuario->activo;
        $usuario->save();

        $usuarioActualizado = $usuario->fresh();

        return [
            'usuario' => $usuarioActualizado,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => (bool) $usuarioActualizado->activo,
        ];
    }
}