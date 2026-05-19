<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;

class UsuarioController extends Controller
{
    public function registrar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'rol'      => 'nullable|in:admin,ganadero,veterinario'
        ]);

        $user = User::create([
            'name'     => $datos['name'],
            'email'    => $datos['email'],
            'password' => $datos['password'],
            'rol'      => $datos['rol'] ?? User::ROL_GANADERO
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success(
            'Usuario registrado correctamente',
            [
                'usuario' => $user,
                'token'   => $token
            ],
            201
        );
    }

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

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success(
            'Login exitoso',
            [
                'usuario' => $user,
                'token'   => $token
            ]
        );
    }

    public function perfil(Request $request): JsonResponse
    {
        return ApiResponse::success(
            'Perfil obtenido correctamente',
            $request->user()
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(
            'Logout exitoso'
        );
    }
}