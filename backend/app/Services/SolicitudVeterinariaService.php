<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\SolicitudVeterinaria;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SolicitudVeterinariaService
{
    public function listarVeterinariosDisponibles(): Collection
    {
        return User::where('rol', 'veterinario')
            ->where('activo', true)
            ->select('id', 'name', 'email', 'rol', 'activo')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function crearSolicitudGanadero(
        User $ganadero,
        int $animalId,
        int $veterinarioId,
        string $motivo
    ): SolicitudVeterinaria {
        $animal = Animal::with('finca')
            ->where('id', $animalId)
            ->firstOrFail();

        if (!$animal->finca) {
            abort(422, 'El animal no tiene una finca asociada.');
        }

        if ((int) $animal->finca->user_id !== (int) $ganadero->id) {
            abort(403, 'No puede solicitar revisión para un animal que no pertenece a sus fincas.');
        }

        $veterinario = User::where('id', $veterinarioId)
            ->where('rol', 'veterinario')
            ->where('activo', true)
            ->firstOrFail();

        return SolicitudVeterinaria::create([
            'animal_id' => $animal->id,
            'finca_id' => $animal->finca_id,
            'ganadero_id' => $ganadero->id,
            'veterinario_id' => $veterinario->id,
            'motivo' => $motivo,
            'estado' => 'pendiente',
            'respuesta_veterinario' => null,
            'fecha_solicitud' => now(),
            'fecha_atencion' => null,
        ])->load([
            'animal.raza',
            'animal.finca',
            'finca',
            'ganadero:id,name,email,rol',
            'veterinario:id,name,email,rol',
        ]);
    }

    public function listarSolicitudesDelGanadero(
        User $ganadero
    ): Collection {
        return SolicitudVeterinaria::with([
            'animal.raza',
            'animal.finca',
            'finca',
            'veterinario:id,name,email,rol',
        ])
            ->where('ganadero_id', $ganadero->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function listarSolicitudesDelVeterinario(
        User $veterinario
    ): Collection {
        return SolicitudVeterinaria::with([
            'animal.raza',
            'animal.finca',
            'finca',
            'ganadero:id,name,email,rol',
            'veterinario:id,name,email,rol',
        ])
            ->where('veterinario_id', $veterinario->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function obtenerSolicitudDelVeterinario(
        User $veterinario,
        int $solicitudId
    ): ?SolicitudVeterinaria {
        return SolicitudVeterinaria::where('id', $solicitudId)
            ->where('veterinario_id', $veterinario->id)
            ->first();
    }

    public function responderSolicitudVeterinario(
        User $veterinario,
        int $solicitudId,
        string $estado,
        ?string $respuestaVeterinario
    ): SolicitudVeterinaria {
        $solicitud = SolicitudVeterinaria::where('id', $solicitudId)
            ->where('veterinario_id', $veterinario->id)
            ->firstOrFail();

        $solicitud->update([
            'estado' => $estado,
            'respuesta_veterinario' => $respuestaVeterinario,
            'fecha_atencion' => in_array($estado, ['atendida', 'rechazada'], true)
                ? now()
                : $solicitud->fecha_atencion,
        ]);

        return $solicitud->fresh()->load([
            'animal.raza',
            'animal.finca',
            'finca',
            'ganadero:id,name,email,rol',
            'veterinario:id,name,email,rol',
        ]);
    }
}