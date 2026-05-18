<?php

namespace Tests\Unit;

use App\Domain\Pesajes\IPesajeObserver;
use App\Domain\Pesajes\PesajeSubject;
use App\Models\Pesaje;
use PHPUnit\Framework\TestCase;

/**
 * Prueba unitaria del patrón Observer.
 *
 * Verifica que al llamar registrar(), TODOS los observadores suscritos
 * reciben onPesajeRegistrado() exactamente una vez con el pesaje correcto.
 * Usa mocks de PHPUnit — sin base de datos, sin HTTP.
 */
class PesajeSubjectTest extends TestCase
{
    public function test_todos_los_observadores_suscritos_reciben_la_llamada(): void
    {
        $subject = new PesajeSubject();

        $obs1 = $this->createMock(IPesajeObserver::class);
        $obs2 = $this->createMock(IPesajeObserver::class);
        $obs3 = $this->createMock(IPesajeObserver::class);

        // Pesaje con save() simulado via mock parcial
        $pesaje = $this->getMockBuilder(Pesaje::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();
        $pesaje->method('save')->willReturn(true);
        $pesaje->animal_id     = 42;
        $pesaje->peso_estimado = 350.5;

        // Cada observador debe recibir exactamente 1 llamada con el mismo pesaje
        $obs1->expects($this->once())->method('onPesajeRegistrado')->with($pesaje);
        $obs2->expects($this->once())->method('onPesajeRegistrado')->with($pesaje);
        $obs3->expects($this->once())->method('onPesajeRegistrado')->with($pesaje);

        $subject->suscribir($obs1);
        $subject->suscribir($obs2);
        $subject->suscribir($obs3);

        $subject->registrar($pesaje);
        // PHPUnit verifica las expectativas al final del test automáticamente
    }

    public function test_observador_desuscrito_no_recibe_llamada(): void
    {
        $subject = new PesajeSubject();

        $obs1 = $this->createMock(IPesajeObserver::class);
        $obs2 = $this->createMock(IPesajeObserver::class);

        $pesaje = $this->getMockBuilder(Pesaje::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();
        $pesaje->method('save')->willReturn(true);

        $obs1->expects($this->once())->method('onPesajeRegistrado');
        $obs2->expects($this->never())->method('onPesajeRegistrado');

        $subject->suscribir($obs1);
        $subject->suscribir($obs2);
        $subject->desuscribir($obs2);

        $subject->registrar($pesaje);
    }

    public function test_agregar_cuarto_observador_no_modifica_subject(): void
    {
        // Este test demuestra Open/Closed: AlertaSMS se agrega sin tocar PesajeSubject
        $subject = new PesajeSubject();

        $alertaSMS = $this->createMock(IPesajeObserver::class);

        $pesaje = $this->getMockBuilder(Pesaje::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();
        $pesaje->method('save')->willReturn(true);

        $alertaSMS->expects($this->once())->method('onPesajeRegistrado')->with($pesaje);

        // Solo se suscribe el nuevo observador — el subject NO fue modificado
        $subject->suscribir($alertaSMS);
        $subject->registrar($pesaje);
    }
}
