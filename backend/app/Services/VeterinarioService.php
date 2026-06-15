<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\FincaVeterinario;
use App\Models\PerfilVeterinario;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class VeterinarioService
{
    public function obtenerPerfil(User $veterinario): array
    {
        $perfil = PerfilVeterinario::firstOrCreate(
            [
                'user_id' => $veterinario->id,
            ],
            [
                'codigo_colegiado' => null,
                'telefono_urgencias' => null,
                'especialidad' => null,
            ]
        );

        $totalFincas = FincaVeterinario::where('veterinario_id', $veterinario->id)
            ->where('activo', true)
            ->count();

        $fincaIds = $this->obtenerFincaIdsAsignadas($veterinario);

        $totalAnimales = Animal::whereIn('finca_id', $fincaIds)
            ->count();

        return [
            'usuario' => $veterinario,
            'perfil_veterinario' => $perfil,
            'total_fincas' => $totalFincas,
            'total_animales' => $totalAnimales,
        ];
    }

    public function actualizarPerfil(
        User $veterinario,
        array $datos
    ): PerfilVeterinario {
        $perfil = PerfilVeterinario::firstOrCreate(
            [
                'user_id' => $veterinario->id,
            ]
        );

        $perfil->update([
            'codigo_colegiado' => $datos['codigo_colegiado'] ?? $perfil->codigo_colegiado,
            'telefono_urgencias' => $datos['telefono_urgencias'] ?? $perfil->telefono_urgencias,
            'especialidad' => $datos['especialidad'] ?? $perfil->especialidad,
        ]);

        return $perfil->fresh();
    }

    public function obtenerFincasAsignadas(User $veterinario): Collection
{
    return Finca::with([
        'propietario:id,name,email',
    ])
        ->whereIn('id', $this->obtenerFincaIdsAsignadas($veterinario))
        ->orderBy('nombre', 'asc')
        ->get();
}

    public function obtenerAnimalesAsignados(User $veterinario): Collection
    {
        return Animal::with([
            'raza',
            'finca',
        ])
            ->whereIn('finca_id', $this->obtenerFincaIdsAsignadas($veterinario))
            ->orderBy('id', 'desc')
            ->get();
    }

    public function obtenerDetalleAnimal(
        User $veterinario,
        int $animalId
    ): ?Animal {
        $animal = Animal::with([
            'raza',
            'finca',
            'pesajes.fuente',
        ])
            ->where('id', $animalId)
            ->whereIn('finca_id', $this->obtenerFincaIdsAsignadas($veterinario))
            ->first();

        if (!$animal) {
            return null;
        }

        $animal->pesajes = $animal->pesajes
            ->sortByDesc(function ($pesaje) {
                return $pesaje->fecha . ' ' . $pesaje->created_at . ' ' . $pesaje->id;
            })
            ->values();

        return $animal;
    }

    public function obtenerFincaIdsAsignadas(User $veterinario): array
    {
        return FincaVeterinario::where('veterinario_id', $veterinario->id)
            ->where('activo', true)
            ->pluck('finca_id')
            ->toArray();
    }
}