<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;
use App\Services\UsuarioService;
use App\Services\AuditoriaService;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly UsuarioService $usuarioService,
        private readonly AuditoriaService $auditoriaService
    ) {}

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
            'rol'      => $datos['rol'] ?? User::ROL_GANADERO,
            'activo'   => true,
        ]);

        $this->auditoriaService->registrarRegistroUsuario(
            $user,
            $request
        );

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

        if (!$user->activo) {
            return ApiResponse::error(
                'La cuenta está desactivada. Contacte al administrador.',
                [],
                403
            );
        }

        $this->auditoriaService->registrarLogin(
            $user,
            $request
        );

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

    public function perfilCompleto(Request $request): JsonResponse
    {
        $datos = $this->usuarioService
            ->obtenerPerfilCompleto(
                $request->user()
            );

        return ApiResponse::success(
            'Perfil completo obtenido correctamente',
            $datos
        );
    }

    public function actualizarPerfil(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
        ]);

        $datosAnteriores = [
            'name' => $usuario->name,
            'email' => $usuario->email,
        ];

        $usuario->update([
            'name'  => $datos['name'],
            'email' => $datos['email'],
        ]);

        $usuarioActualizado = $usuario->fresh();

        $this->auditoriaService->registrar(
            accion: 'ACTUALIZAR_PERFIL',
            modulo: 'Usuarios',
            descripcion: 'El usuario actualizó su perfil.',
            entidadTipo: 'User',
            entidadId: $usuarioActualizado->id,
            datosAnteriores: $datosAnteriores,
            datosNuevos: [
                'name' => $usuarioActualizado->name,
                'email' => $usuarioActualizado->email,
            ],
            usuario: $usuarioActualizado,
            request: $request
        );

        return ApiResponse::success(
            'Perfil actualizado correctamente',
            $usuarioActualizado
        );
    }

    public function listarUsuariosAdmin(Request $request): JsonResponse
    {
        if ($request->user()->rol !== 'admin') {
            return ApiResponse::error(
                'No tiene permisos para acceder al panel administrativo.',
                [],
                403
            );
        }

        $usuarios = $this->usuarioService->listarUsuariosAdmin();

        return ApiResponse::success(
            'Usuarios obtenidos correctamente',
            $usuarios
        );
    }

    public function cambiarEstadoUsuarioAdmin(
        Request $request,
        int $id
    ): JsonResponse {

        if ($request->user()->rol !== 'admin') {
            return ApiResponse::error(
                'No tiene permisos para realizar esta acción.',
                [],
                403
            );
        }

        if ($request->user()->id === $id) {
            return ApiResponse::error(
                'No puede activar o desactivar su propia cuenta.',
                [],
                422
            );
        }

        $resultado = $this->usuarioService
            ->cambiarEstadoUsuarioAdmin($id);

        $usuario = $resultado['usuario'];
        $estadoAnterior = $resultado['estado_anterior'];
        $estadoNuevo = $resultado['estado_nuevo'];

        $this->auditoriaService->registrarCambioEstadoUsuario(
            admin: $request->user(),
            usuarioAfectado: $usuario,
            estadoAnterior: $estadoAnterior,
            estadoNuevo: $estadoNuevo,
            request: $request
        );

        return ApiResponse::success(
            $usuario->activo
                ? 'Usuario activado correctamente'
                : 'Usuario desactivado correctamente',
            $usuario
        );
    }
}