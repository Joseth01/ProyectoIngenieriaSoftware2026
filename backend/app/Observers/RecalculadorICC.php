<?php

namespace App\Observers;

use App\Domain\Pesajes\IPesajeObserver;
use App\Domain\Razas\IRazaFactory;
use App\Models\Pesaje;
use Illuminate\Support\Facades\Log;

/**
 * ConcreteObserver 2: recalcula el Índice de Condición Corporal del animal.
 */
class RecalculadorICC implements IPesajeObserver
{
    public function __construct(private readonly IRazaFactory $razaFactory) {}

    public function onPesajeRegistrado(Pesaje $pesaje): void
    {
        $animal = $pesaje->animal;

        if (!$animal) {
            Log::warning("[RecalculadorICC] Animal no encontrado para pesaje_id: {$pesaje->id}");
            return;
        }
        try {
            // Delega los parámetros zootécnicos a la abstracción correcta.
            // Ahora RecalculadorICC solo ORQUESTA — no conoce ningún valor concreto.
            $razaDominio = $this->razaFactory->create($animal->raza?->nombre ?? 'brahman');
            $icc = $razaDominio->calcularICC($pesaje->peso_estimado);

            Log::info("[RecalculadorICC] ICC recalculado — animal_id: {$pesaje->animal_id} → ICC: {$icc}");
        } catch (\InvalidArgumentException $e) {
            Log::warning("[RecalculadorICC] Raza no reconocida para animal_id: {$pesaje->animal_id}");
        }
       
    }
}
