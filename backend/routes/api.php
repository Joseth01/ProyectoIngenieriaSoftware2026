<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PesajeController;
use App\Http\Controllers\FincaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\BitacoraController;

// RUTAS PÚBLICAS DE USUARIOS
Route::prefix('usuarios')->group(function () {
    Route::post('/registrar', [UsuarioController::class, 'registrar']);
    Route::post('/registro', [UsuarioController::class, 'registrar']);
    Route::post('/login', [UsuarioController::class, 'login']);
});

// RUTAS PROTEGIDAS
Route::middleware('auth:sanctum')->group(function () {

    // USUARIOS
    Route::prefix('usuarios')->group(function () {
        Route::get('/perfil', [UsuarioController::class, 'perfil']);
        Route::put('/perfil', [UsuarioController::class, 'actualizarPerfil']);
        Route::post('/logout', [UsuarioController::class, 'logout']);
        Route::get('/perfil-completo', [UsuarioController::class, 'perfilCompleto']);
    });
    // ADMINISTRACIÓN
        Route::prefix('admin')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'listarUsuariosAdmin']);
        Route::patch('/usuarios/{id}/estado', [UsuarioController::class, 'cambiarEstadoUsuarioAdmin']);

        Route::get('/bitacoras', [BitacoraController::class, 'listar']);
        });
    // FINCAS
    Route::prefix('fincas')->group(function () {
        Route::post('/', [FincaController::class, 'crearFinca']);
        Route::get('/', [FincaController::class, 'listarFincas']);
        Route::get('/usuario/{id}', [FincaController::class, 'obtenerFincasPorUsuario']);
        Route::get('/{id}', [FincaController::class, 'obtenerFinca']);
        Route::put('/{id}', [FincaController::class, 'actualizarFinca']);
        Route::delete('/{id}', [FincaController::class, 'eliminarFinca']);
    });

    // ANIMALES
    Route::prefix('animales')->group(function () {
        Route::get('/razas', [AnimalController::class, 'razas']);

        Route::post('/', [AnimalController::class, 'crear']);
        Route::get('/', [AnimalController::class, 'listar']);
        Route::get('/arete/{arete}', [AnimalController::class, 'buscarPorArete']);
        Route::get('/{id}/historial', [AnimalController::class, 'historial']);
        Route::get('/{id}', [AnimalController::class, 'obtener']);
        Route::put('/{id}', [AnimalController::class, 'actualizar']);
        Route::delete('/{id}', [AnimalController::class, 'eliminar']);
    });

    // PESAJES
    Route::prefix('pesajes')->group(function () {
        Route::get('/', [PesajeController::class, 'listar']);
        Route::get('/animal/{animal_id}', [PesajeController::class, 'obtenerPorAnimal']);
        Route::post('/', [PesajeController::class, 'crear']);
        Route::get('/{id}', [PesajeController::class, 'obtener']);
        Route::put('/{id}', [PesajeController::class, 'actualizar']);
        Route::delete('/{id}', [PesajeController::class, 'eliminar']);
        Route::post('/estimar-peso', [PesajeController::class, 'estimarPeso']);
        Route::post('/confirmar-ia', [PesajeController::class, 'confirmarIA']);
    });

    // REPORTES
    Route::prefix('reportes')->group(function () {
        Route::get('/', [ReporteController::class, 'listar']);
        Route::get('/usuario/{user_id}', [ReporteController::class, 'obtenerPorUsuario']);
        Route::get('/finca/{finca_id}', [ReporteController::class, 'reportePorFinca']);
        Route::post('/', [ReporteController::class, 'crear']);
        Route::get('/{id}', [ReporteController::class, 'obtener']);
        Route::put('/{id}', [ReporteController::class, 'actualizar']);
        Route::delete('/{id}', [ReporteController::class, 'eliminar']);
    });

    // IMÁGENES
    Route::prefix('imagenes')->group(function () {
        Route::get('/', [ImagenController::class, 'listar']);
        Route::get('/animal/{animal_id}', [ImagenController::class, 'obtenerPorAnimal']);
        Route::post('/', [ImagenController::class, 'crear']);
        Route::get('/{id}', [ImagenController::class, 'obtener']);
        Route::put('/{id}', [ImagenController::class, 'actualizar']);
        Route::delete('/{id}', [ImagenController::class, 'eliminar']);
    });
});