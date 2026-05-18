<?php

namespace App\Services;

use App\Domain\Animales\IAnimalRepository;
use App\Domain\Razas\IRazaFactory;

/**
 * Punto de creación 2 refactorizado con Repository + Factory.
 *
 * ANTES (problema):
 *   Animal::where('finca_id', $id)->with('pesajes')->get()  ← Eloquent directo
 *   new Brahman() / new Nelore() para calcular ICC           ← constructores hardcoded
 *
 * DESPUÉS: habla el lenguaje del dominio, sin rastro de Eloquent ni constructores concretos.
 */
class ReporteService
{
    public function __construct(
        private readonly IAnimalRepository $animalRepository,
        private readonly IRazaFactory      $razaFactory
    ) {}

    public function reportePorFinca(int $fincaId): array
    {
        $animales = $this->animalRepository->findAllByFinca($fincaId);

        return array_map(function ($animal) {
            $raza    = $animal->raza;
            $pesajes = $animal->pesajes ?? collect();

            // Usa factory para obtener comportamiento de dominio de la raza
            $razaDominio = null;
            try {
                $razaDominio = $this->razaFactory->create($raza?->nombre ?? 'brahman');
            } catch (\InvalidArgumentException) {
                // Raza en BD sin implementación de dominio todavía
            }

            $ultimoPeso = $pesajes->sortByDesc('fecha')->first()?->peso_estimado;
            $icc = $razaDominio && $ultimoPeso
                ? $razaDominio->calcularICC($ultimoPeso)
                : null;

            return [
                'arete'        => $animal->numero_arete,
                'nombre'       => $animal->nombre,
                'raza'         => $raza?->nombre,
                'icc'          => $icc,
                'total_pesajes' => $pesajes->count(),
                'ultimo_peso'  => $ultimoPeso,
            ];
        }, $animales);
    }
}
