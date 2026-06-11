<?php

namespace App\Services;

use App\Models\User;
use App\Models\Finca;
use App\Models\Animal;

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
}