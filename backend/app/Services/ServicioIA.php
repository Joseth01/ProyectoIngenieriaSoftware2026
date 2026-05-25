<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ServicioIA
{
    public function analizarImagen($rutaImagen)
    {
        $respuesta = Http::attach(
            'imagen',
            file_get_contents($rutaImagen),
            basename($rutaImagen)
        )->post('http://127.0.0.1:5000/detectar');

        return $respuesta->json();
    }
}