<?php

namespace App\Observers;

use App\Domain\Pesajes\IPesajeObserver;
use App\Models\Pesaje;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ConcreteObserver 3: dispara webhook a la API de SENASA.
 */
class WebhookSenasa implements IPesajeObserver
{
    private string $urlWebhook;

    public function __construct(string $urlWebhook = 'https://api.senasa.go.cr/bovinos/pesaje')
    {
        $this->urlWebhook = $urlWebhook;
    }

    public function onPesajeRegistrado(Pesaje $pesaje): void
    {
        try {
            // En producción: Http::post($this->urlWebhook, [...])
            Log::info("[WebhookSenasa] Webhook disparado — pesaje_id: {$pesaje->id}, animal_id: {$pesaje->animal_id}");
        } catch (\Exception $e) {
            Log::error("[WebhookSenasa] Error al disparar webhook: " . $e->getMessage());
        }
    }
}
