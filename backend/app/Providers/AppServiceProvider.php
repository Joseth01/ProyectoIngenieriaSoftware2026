<?php

namespace App\Providers;

use App\Domain\Animales\IAnimalLector;
use App\Domain\Animales\IAnimalRepositoryV2;
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

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Factory (singleton: el mapa de razas se construye una vez por request) ──
        $this->app->singleton(IRazaFactory::class, RazaFactory::class);

        // ── Repository + ISP: interfaces segregadas apuntando a la misma implementación ──
        // AnimalService usa IAnimalRepositoryV2 (lectura + escritura).
        // ReporteService usa IAnimalLector (solo lectura → ISP).
        $this->app->bind(IAnimalRepositoryV2::class, EloquentAnimalRepository::class);
        $this->app->bind(IAnimalLector::class, EloquentAnimalRepository::class);

        // ── Strategy: EstimadorPesoService con fallback inyectado (SRP + DIP) ──
        // La política de fallback se decide aquí, no dentro del servicio.
        $this->app->bind(EstimadorPesoServiceReparado::class, function () {
            return new EstimadorPesoServiceReparado(
                algoritmo: new AlgoritmoYolov8(),
                fallback:  new AlgoritmoTablaReferencia()
            );
        });

        // ── Observer: PesajeSubject preconstruido con todos sus observers (DIP + OCP) ──
        // Agregar un 5° observer = una línea aquí. PesajeSubject y los demás no se tocan.
        $this->app->singleton(PesajeSubject::class, function () {
            $subject = new PesajeSubject();
            $subject->suscribir($this->app->make(NotificadorPropietario::class));
            $subject->suscribir($this->app->make(RecalculadorICCReparado::class));
            $subject->suscribir($this->app->make(WebhookSenasa::class));
            $subject->suscribir($this->app->make(AlertaSMS::class));
            return $subject;
        });

        // ── OCP: AlgoritmoFactory con mapa extensible (singleton para reutilizar el mapa) ──
        $this->app->singleton(AlgoritmoFactory::class);
    }

    public function boot(): void
    {
        //
    }
}
