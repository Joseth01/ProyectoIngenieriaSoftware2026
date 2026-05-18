<?php
// ✅ REPARACIÓN SRP — RecalculadorICC con responsabilidad única
// Archivo: app/Observers/Reparado/RecalculadorICCReparado.php
//
// VIOLACIÓN ORIGINAL (app/Observers/RecalculadorICC.php):
//   $pesoPromedio = 450.0;  // parámetro de raza hardcodeado aquí
//   $icc = round($pesaje->peso_estimado / $pesoPromedio * 2.8, 2);
//
// RecalculadorICC tenía DOS responsabilidades:
//   1. Calcular el ICC  ← responsabilidad del observador
//   2. Conocer los parámetros zootécnicos de la raza ← responsabilidad de IRazaFactory
//
// Si el coeficiente de una raza cambia, hay que modificar RecalculadorICC.
// Si se agrega una raza nueva, hay que modificar RecalculadorICC.
// Son dos actores distintos que obligan a tocar la misma clase.

namespace App\Observers\Reparado;

use App\Domain\Pesajes\IPesajeObserver;
use App\Domain\Razas\IRazaFactory;
use App\Models\Pesaje;
use Illuminate\Support\Facades\Log;

class RecalculadorICCReparado implements IPesajeObserver
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
