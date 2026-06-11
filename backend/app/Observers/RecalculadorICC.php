<?php

namespace App\Observers;

use App\Domain\Pesajes\IPesajeObserver;
use App\Models\Pesaje;
use Illuminate\Support\Facades\Log;

/**
 * ConcreteObserver 2: recalcula el Índice de Condición Corporal del animal.
 */
class RecalculadorICC implements IPesajeObserver
{
    public function onPesajeRegistrado(Pesaje $pesaje): void
    {
        $animal = $pesaje->animal;

        if (!$animal) {
            Log::warning("[RecalculadorICC] Animal no encontrado para pesaje_id: {$pesaje->id}");
            return;
        }

        // Cálculo simplificado del ICC: peso / peso_promedio_raza * coeficiente
        $pesoPromedio = 450.0; // En producción: obtener de RazaFactory
        $icc = round($pesaje->peso_estimado / $pesoPromedio * 2.8, 2);

        Log::info("[RecalculadorICC] ICC recalculado para animal_id: {$pesaje->animal_id} → ICC: {$icc}");
    }
}
