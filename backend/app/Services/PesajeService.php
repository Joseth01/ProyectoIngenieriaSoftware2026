<?php
// ✅ REPARACIÓN DIP — orquestador inyectado, sin new concretos
// Archivo: app/Services/Reparado/PesajeService.php
//
// Concentra la orquestación que estaba repartida en el Controller:
// selección de algoritmo, estimación, persistencia y notificación.
// Todas las dependencias llegan por inyección — nada de new XYZ().

namespace App\Services\Reparado;

use App\Domain\Pesajes\PesajeSubject;
use App\Models\Pesaje;

class PesajeService
{
    public function __construct(
        private readonly AlgoritmoFactory       $algoritmoFactory,
        private readonly EstimadorPesoServiceReparado $estimador,
        private readonly PesajeSubject          $subject   // ya preconstruido con observers en AppServiceProvider
    ) {}

    public function registrar(array $datos): array
    {
        // Selección del algoritmo delegada al factory — sin match en el servicio
        $algoritmo = $this->algoritmoFactory->crear($datos['metodo_estimacion'] ?? 'yolov8');
        $this->estimador->setAlgoritmo($algoritmo);

        $resultado = $this->estimador->estimar($datos);

        $pesaje = new Pesaje([
            'animal_id'     => $datos['animal_id'],
            'peso_estimado' => $resultado->pesoKg,
            'peso_real'     => $datos['peso_real'] ?? null,
            'fecha'         => $datos['fecha'],
            'fuente_id'     => $datos['fuente_id'] ?? null,
        ]);

        // Subject ya tiene sus observers — el servicio no los conoce
        $pesaje = $this->subject->registrar($pesaje);

        return array_merge($pesaje->toArray(), ['estimacion' => $resultado->toArray()]);
    }
}

// ── Registro en AppServiceProvider: ──────────────────────────
// $this->app->singleton(PesajeSubject::class, function () {
//     $subject = new PesajeSubject();
//     $subject->suscribir(app(NotificadorPropietario::class));
//     $subject->suscribir(app(RecalculadorICCReparado::class));
//     $subject->suscribir(app(WebhookSenasa::class));
//     $subject->suscribir(app(AlertaSMS::class));
//     return $subject;
// });
//
// $this->app->bind(PesajeService::class);   // autowire
