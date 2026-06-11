<?php

namespace App\Domain\Pesajes;

use App\Models\Pesaje;

/**
 * Subject (Observable) del patrón Observer.
 *
 * Mantiene la lista de observadores y dispara notificaciones.
 * El controlador llama SOLO a registrar() — no sabe que existe
 * NotificadorPropietario, RecalculadorICC ni WebhookSenasa.
 * Agregar AlertaSMS = suscribir(new AlertaSMS()), sin tocar este archivo.
 */
class PesajeSubject
{
    private array $observadores = [];

    public function suscribir(IPesajeObserver $observador): void
    {
        $this->observadores[] = $observador;
    }

    public function desuscribir(IPesajeObserver $observador): void
    {
        $this->observadores = array_values(array_filter(
            $this->observadores,
            fn($o) => $o !== $observador
        ));
    }

    /**
     * Persiste el pesaje y notifica a todos los observadores suscritos.
     * notificar() es privado: solo este Subject decide cuándo y cómo disparar.
     */
    public function registrar(Pesaje $pesaje): Pesaje
    {
        $pesaje->save();
        $this->notificar($pesaje);
        return $pesaje;
    }

    private function notificar(Pesaje $pesaje): void
    {
        foreach ($this->observadores as $observador) {
            $observador->onPesajeRegistrado($pesaje);
        }
    }
}
