<?php

namespace App\Helpers;

class ApiResponse
{
    /**
     * Respuesta exitosa estándar
     */
    public static function success(
        string $mensaje,
        $datos = null,
        int $codigo = 200
    ) {
        return response()->json([
            'exito' => true,
            'mensaje' => $mensaje,
            'datos' => $datos
        ], $codigo);
    }

    /**
     * Respuesta de error estándar
     */
    public static function error(
        string $mensaje,
        array $errores = [],
        int $codigo = 400
    ) {
        return response()->json([
            'exito' => false,
            'mensaje' => $mensaje,
            'errores' => $errores
        ], $codigo);
    }
}