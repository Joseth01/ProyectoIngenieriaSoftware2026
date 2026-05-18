<?php
// ✅ TEST que demuestra la violación LSP (versión original) y la corrección
// Archivo: tests/Unit/InMemoryRepositoryLSPTest.php

namespace Tests\Unit;

use App\Infrastructure\Repositories\InMemoryAnimalRepositoryReparado;
use App\Models\Animal;
use App\Models\Pesaje;
use App\Models\Raza;
use PHPUnit\Framework\TestCase;

/**
 * Este test verifica que InMemoryAnimalRepository honra el mismo contrato
 * conductual que EloquentAnimalRepository:
 * findAllByFinca() devuelve animales con sus relaciones 'raza' y 'pesajes' cargadas.
 *
 * Con la implementación ORIGINAL (sin reparar), este test FALLA:
 *   $animal->raza retorna null → el reporte muestra raza: null en tests
 *   $animal->pesajes retorna null → count() lanza error
 *
 * Con la implementación REPARADA, el test pasa y el comportamiento
 * es equivalente al de Eloquent en producción.
 */
class InMemoryRepositoryLSPTest extends TestCase
{
    private InMemoryAnimalRepositoryReparado $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryAnimalRepositoryReparado();
    }

    public function test_findAllByFinca_devuelve_animales_con_relacion_raza_cargada(): void
    {
        $raza = new Raza();
        $raza->id = 1;
        $raza->nombre = 'Brahman';
        $this->repo->agregarRaza($raza);

        $animal = new Animal();
        $animal->id = 1;
        $animal->finca_id = 10;
        $animal->raza_id = 1;
        $animal->numero_arete = 'CR-001';
        $animal->nombre = 'Toro';
        $this->repo->save($animal);

        $resultado = $this->repo->findAllByFinca(10);

        $this->assertCount(1, $resultado);
        // ✅ La relación 'raza' debe estar cargada — igual que Eloquent con ::with('raza')
        $this->assertNotNull($resultado[0]->raza, 'La relación raza debe estar cargada');
        $this->assertEquals('Brahman', $resultado[0]->raza->nombre);
    }

    public function test_findAllByFinca_devuelve_animales_con_relacion_pesajes_cargada(): void
    {
        $animal = new Animal();
        $animal->id = 2;
        $animal->finca_id = 10;
        $animal->raza_id = 1;
        $animal->numero_arete = 'CR-002';
        $this->repo->save($animal);

        $pesaje = new Pesaje();
        $pesaje->id = 1;
        $pesaje->animal_id = 2;
        $pesaje->peso_estimado = 350.0;
        $this->repo->agregarPesaje($pesaje);

        $resultado = $this->repo->findAllByFinca(10);

        // ✅ La relación 'pesajes' debe estar cargada — igual que Eloquent
        $this->assertNotNull($resultado[0]->pesajes, 'La relación pesajes debe estar cargada');
        $this->assertEquals(1, $resultado[0]->pesajes->count());
        $this->assertEquals(350.0, $resultado[0]->pesajes->first()->peso_estimado);
    }

    public function test_contrato_LSP_comportamiento_equivalente(): void
    {
        // Este test representa la garantía de LSP:
        // si el código cliente funciona con EloquentAnimalRepository,
        // también debe funcionar con InMemoryAnimalRepositoryReparado.
        $animal = new Animal();
        $animal->id = 3;
        $animal->finca_id = 20;
        $animal->raza_id = 99;
        $this->repo->save($animal);

        $resultado = $this->repo->findAllByFinca(20);

        // El acceso a $animal->pesajes->count() no lanza excepción
        // (en la versión SIN reparar, pesajes es null y count() falla)
        $this->assertCount(0, $resultado[0]->pesajes);
    }
}
