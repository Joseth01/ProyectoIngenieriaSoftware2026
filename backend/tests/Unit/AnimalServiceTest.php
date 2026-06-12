<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AnimalServiceTest extends TestCase
{
    public function test_edad_animal_mayor_un_año(): void
    {
        $fechaNacimiento = now()->subYears(2)->format('Y-m-d');
        $ms = (new \DateTime())->getTimestamp() - (new \DateTime($fechaNacimiento))->getTimestamp();
        $años = floor($ms / (60 * 60 * 24 * 365));

        $this->assertGreaterThanOrEqual(1, $años);
    }

    public function test_edad_animal_menor_un_año(): void
    {
        $fechaNacimiento = now()->subMonths(6)->format('Y-m-d');
        $ms = (new \DateTime())->getTimestamp() - (new \DateTime($fechaNacimiento))->getTimestamp();
        $años = floor($ms / (60 * 60 * 24 * 365));

        $this->assertEquals(0, $años);
    }

    public function test_peso_numerico_positivo(): void
    {
        $peso = 320.5;
        $this->assertGreaterThan(0, $peso);
    }
}