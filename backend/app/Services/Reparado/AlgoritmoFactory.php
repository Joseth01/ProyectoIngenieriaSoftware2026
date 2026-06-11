<?php
// ✅ REPARACIÓN OCP — factory que encapsula la selección de algoritmo
// Archivo: app/Services/Reparado/AlgoritmoFactory.php
//
// VIOLACIÓN ORIGINAL (app/Http/Controllers/PesajeController.php, líneas 62-66):
//
//   $algoritmo = match ($datos['metodo_estimacion']) {
//       'regresion' => new AlgoritmoRegresionLineal(),
//       'tabla'     => new AlgoritmoTablaReferencia(),
//       default     => new AlgoritmoYolov8(),
//   };
//
// El match vive en el Controller. Si se agrega el método 'morfometria_3d',
// hay que abrir PesajeController y modificarlo — violando OCP.
// Además, el Controller (capa HTTP) conoce nombres de clases de infraestructura.

namespace App\Services\Reparado;

use App\Domain\Estimacion\IAlgoritmoEstimacion;
use App\Estimacion\AlgoritmoRegresionLineal;
use App\Estimacion\AlgoritmoTablaReferencia;
use App\Estimacion\AlgoritmoYolov8;
use InvalidArgumentException;

class AlgoritmoFactory
{
    /** @var array<string, class-string<IAlgoritmoEstimacion>> */
    private array $mapa = [
        'yolov8'    => AlgoritmoYolov8::class,
        'regresion' => AlgoritmoRegresionLineal::class,
        'tabla'     => AlgoritmoTablaReferencia::class,
    ];

    public function crear(string $metodo): IAlgoritmoEstimacion
    {
        $clave = strtolower(trim($metodo));

        if (!isset($this->mapa[$clave])) {
            throw new InvalidArgumentException(
                "Método de estimación desconocido: '{$metodo}'. Válidos: " . implode(', ', array_keys($this->mapa))
            );
        }

        $clase = $this->mapa[$clave];
        return new $clase();
    }

    /**
     * Para agregar 'morfometria_3d':
     * 1. Crear AlgoritmoMorfometria3D implements IAlgoritmoEstimacion
     * 2. Añadir 'morfometria_3d' => AlgoritmoMorfometria3D::class aquí
     * PesajeController NO se toca. Cerrado a modificación, abierto a extensión.
     */
    public function registrar(string $clave, string $clase): void
    {
        $this->mapa[strtolower($clave)] = $clase;
    }

    public function metodosDisponibles(): array
    {
        return array_keys($this->mapa);
    }
}
