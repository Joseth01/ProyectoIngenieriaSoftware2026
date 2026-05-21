<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;

class UsuarioController extends Controller
{
    // REGISTRO
    public function registrar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'rol'      => 'required|in:ganadero,veterinario',
        ]);

        $user = User::create([
            'name'     => $datos['name'],
            'email'    => $datos['email'],
            'password' => bcrypt($datos['password']),
            'rol'      => $datos['rol'],
        ]);

        return ApiResponse::success(
            'Usuario registrado correctamente',
            $user,
            201
        );
    }

    // LOGIN
    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $datos['email'])->first();

        if (!$user || !Hash::check($datos['password'], $user->password)) {
            return ApiResponse::error(
                'Credenciales incorrectas',
                [],
                401
            );
        }

        return ApiResponse::success(
            'Login exitoso',
            $user
        );
    }

    // PERFIL (por ID)
    public function perfil($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return ApiResponse::error(
                'Usuario no encontrado',
                [],
                404
            );
        }

        return ApiResponse::success(
            'Perfil obtenido correctamente',
            $user
        );
    }

    // LOGOUT (simulado)
    public function logout(): JsonResponse
    {
        return ApiResponse::success(
            'Logout simulado correctamente'
        );
    }
}