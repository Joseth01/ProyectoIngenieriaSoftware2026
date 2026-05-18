<?php
// ✅ REPARACIÓN DIP — ServiceProvider como único lugar de cableado concreto
// Archivo: app/Providers/AppServiceProviderReparado.php
//
// VIOLACIÓN ORIGINAL distribuida en PesajeController::crear():
//
//   // El controller CONSTRUYE sus propias dependencias concretas
//   $algoritmo = match ($datos['metodo_estimacion']) {
//       'regresion' => new AlgoritmoRegresionLineal(),   // ← new concreto
//       'tabla'     => new AlgoritmoTablaReferencia(),   // ← new concreto
//       default     => new AlgoritmoYolov8(),            // ← new concreto
//   };
//   $estimador  = new EstimadorPesoService($algoritmo); // ← new concreto
//   $subject    = new PesajeSubject();                  // ← new concreto
//   $subject->suscribir(new NotificadorPropietario());  // ← new concreto
//   $subject->suscribir(new RecalculadorICC());         // ← new concreto
//   $subject->suscribir(new WebhookSenasa());           // ← new concreto
//   $subject->suscribir(new AlertaSMS());               // ← new concreto
//
// DIP requiere que los módulos de alto nivel (controller, service) dependan
// de abstracciones, no de clases concretas. La construcción de objetos concretos
// debe estar en el borde del sistema — el Composition Root — que en Laravel es
// el ServiceProvider.

namespace App\Providers;

use App\Domain\Animales\IAnimalLector;
use App\Domain\Animales\IAnimalRepositoryV2;
use App\Domain\Estimacion\IAlgoritmoEstimacion;
use App\Domain\Pesajes\PesajeSubject;
use App\Domain\Razas\IRazaFactory;
use App\Domain\Razas\RazaFactory;
use App\Estimacion\AlgoritmoTablaReferencia;
use App\Estimacion\AlgoritmoYolov8;
use App\Infrastructure\Repositories\EloquentAnimalRepository;
use App\Observers\AlertaSMS;
use App\Observers\NotificadorPropietario;
use App\Observers\Reparado\RecalculadorICCReparado;
use App\Observers\WebhookSenasa;
use App\Services\Reparado\AlgoritmoFactory;
use App\Services\Reparado\EstimadorPesoServiceReparado;
use Illuminate\Support\ServiceProvider;

class AppServiceProviderReparado extends ServiceProvider
{
    public function register(): void
    {
        // ── Factory y Repository (sin cambios respecto al original) ──
        $this->app->singleton(IRazaFactory::class, RazaFactory::class);
        $this->app->bind(IAnimalRepositoryV2::class, EloquentAnimalRepository::class);

        // ISP: ReporteService solo necesita IAnimalLector → misma instancia concreta
        $this->app->bind(IAnimalLector::class, EloquentAnimalRepository::class);

        // ── SRP + DIP: EstimadorPesoService con fallback inyectado ───
        $this->app->bind(EstimadorPesoServiceReparado::class, function () {
            return new EstimadorPesoServiceReparado(
                algoritmo: new AlgoritmoYolov8(),           // principal
                fallback:  new AlgoritmoTablaReferencia()   // política decidida aquí, no en el servicio
            );
        });

        // ── DIP: PesajeSubject preconstruido con todos sus observers ─
        // Agregar AlertaSMS no requirió tocar PesajeSubject, ni los observers
        // existentes, ni el controller. Solo esta línea en este archivo.
        $this->app->singleton(PesajeSubject::class, function () {
            $subject = new PesajeSubject();
            $subject->suscribir($this->app->make(NotificadorPropietario::class));
            $subject->suscribir($this->app->make(RecalculadorICCReparado::class));
            $subject->suscribir($this->app->make(WebhookSenasa::class));
            $subject->suscribir($this->app->make(AlertaSMS::class));
            return $subject;
        });

        // ── OCP: AlgoritmoFactory con mapa extensible ────────────────
        $this->app->singleton(AlgoritmoFactory::class);
    }

    public function boot(): void
    {
        //
    }
}
