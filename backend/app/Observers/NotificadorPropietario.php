<?php

namespace App\Observers;

use App\Domain\Pesajes\IPesajeObserver;
use App\Models\Pesaje;
use Illuminate\Support\Facades\Log;

/**
 * ConcreteObserver 1: notifica al propietario de la finca por email.
 */
class NotificadorPropietario implements IPesajeObserver
{
    public function onPesajeRegistrado(Pesaje $pesaje): void
    {
        // En producción: Mail::to($propietario->email)->send(new PesajeRegistradoMail($pesaje))
        Log::info("[NotificadorPropietario] Email enviado — animal_id: {$pesaje->animal_id}, peso: {$pesaje->peso_estimado} kg");
    }
}
