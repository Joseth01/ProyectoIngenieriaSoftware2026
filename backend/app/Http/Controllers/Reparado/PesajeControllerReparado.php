<?php
// ✅ REPARACIÓN OCP + DIP — PesajeController solo maneja HTTP
// Archivo: app/Http/Controllers/Reparado/PesajeControllerReparado.php
//
// VIOLACIÓN ORIGINAL (app/Http/Controllers/PesajeController.php):
//
//   // OCP: el match obliga a abrir el controller para cada nuevo algoritmo
//   $algoritmo = match ($datos['metodo_estimacion']) { ... };
//   $estimador = new EstimadorPesoService($algoritmo);     // DIP: new concreto
//
//   // DIP: el controller cablea todos los observers manualmente
//   $subject = new PesajeSubject();
//   $subject->suscribir(new NotificadorPropietario());
//   $subject->suscribir(new RecalculadorICC());
//   $subject->suscribir(new WebhookSenasa());
//   $subject->suscribir(new AlertaSMS());
//
// El controller hace: validación HTTP + selección de algoritmo + cableado de observers
// + estimación + persistencia + formateo de respuesta. Son demasiadas responsabilidades.

namespace App\Http\Controllers\Reparado;

use App\Http\Controllers\Controller;
use App\Services\Reparado\PesajeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PesajeControllerReparado extends Controller
{
    // Solo se inyecta PesajeService — el controller NO sabe qué algoritmos ni observers existen
    public function __construct(private readonly PesajeService $pesajeService) {}

    public function crear(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'animal_id'             => 'required|exists:animales,id',
            'fecha'                 => 'required|date',
            'fuente_id'             => 'nullable|exists:fuentes_pesaje,id',
            'peso_real'             => 'nullable|numeric|min:0',
            'metodo_estimacion'     => 'required|string',
            'raza'                  => 'nullable|string',
            'edad_meses'            => 'nullable|integer|min:1',
            'largo_corporal_cm'     => 'nullable|numeric',
            'perimetro_toracico_cm' => 'nullable|numeric',
            'peso_referencia'       => 'nullable|numeric',
        ]);

        // Una sola línea — toda la orquestación vive en PesajeService
        $resultado = $this->pesajeService->registrar($datos);

        return response()->json([
            'exito'   => true,
            'mensaje' => 'Pesaje registrado correctamente',
            'datos'   => $resultado,
        ], 201);
    }
}
