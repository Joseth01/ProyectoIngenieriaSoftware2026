<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;
use App\Services\AuditoriaService;

class BitacoraController extends Controller
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService
    ) {}

    public function listar(Request $request): JsonResponse
    {
        if ($request->user()->rol !== 'admin') {
            return ApiResponse::error(
                'No tiene permisos para consultar la bitácora.',
                [],
                403
            );
        }

        $bitacoras = $this->auditoriaService
            ->listarBitacorasAdmin();

        return ApiResponse::success(
            'Bitácora obtenida correctamente',
            $bitacoras
        );
    }
}