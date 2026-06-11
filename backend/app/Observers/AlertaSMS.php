<?php

namespace App\Observers;

use App\Domain\Pesajes\IPesajeObserver;
use App\Models\Pesaje;
use Illuminate\Support\Facades\Log;

/**
 * ConcreteObserver 4 (NUEVO): envía alerta SMS al ganadero.
 *
 * Demostración del principio Open/Closed:
 * - PesajeSubject NO fue modificado
 * - NotificadorPropietario NO fue modificado
 * - RecalculadorICC NO fue modificado
 * - WebhookSenasa NO fue modificado
 * Solo se creó esta clase nueva y se suscribió en AppServiceProvider.
 */
class AlertaSMS implements IPesajeObserver
{
    public function onPesajeRegistrado(Pesaje $pesaje): void
    {
        // En producción: Twilio o similar
        Log::info("[AlertaSMS] SMS enviado — animal_id: {$pesaje->animal_id}, peso: {$pesaje->peso_estimado} kg");
    }
}
