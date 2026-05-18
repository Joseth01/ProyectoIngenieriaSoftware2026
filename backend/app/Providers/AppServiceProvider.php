<?php

namespace App\Providers;

use App\Domain\Animales\IAnimalRepository;
use App\Domain\Razas\IRazaFactory;
use App\Domain\Razas\RazaFactory;
use App\Infrastructure\Repositories\EloquentAnimalRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Container de Laravel — punto central de configuración de patrones.
 *
 * FACTORY: singleton para que RazaFactory se construya una sola vez por request.
 * REPOSITORY: bind para que IAnimalRepository resuelva a EloquentAnimalRepository.
 *   Cambiar ORM = cambiar solo la línea del bind, sin tocar servicios ni controladores.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Patrón Factory — singleton: el mapa se construye una vez
        $this->app->singleton(IRazaFactory::class, RazaFactory::class);

        // Patrón Repository — bind: intercambiable por DoctrineAnimalRepository
        $this->app->bind(IAnimalRepository::class, EloquentAnimalRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
