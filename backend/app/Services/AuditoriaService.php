<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuditoriaService
{
    public function registrar(
        string $accion,
        ?string $modulo = null,
        ?string $descripcion = null,
        ?string $entidadTipo = null,
        ?int $entidadId = null,
        mixed $datosAnteriores = null,
        mixed $datosNuevos = null,
        ?User $usuario = null,
        ?Request $request = null
    ): void {
        try {
            $requestActual = $request ?? request();

            Bitacora::create([
                'user_id' => $usuario?->id ?? Auth::id(),
                'accion' => $accion,
                'modulo' => $modulo,
                'descripcion' => $descripcion,
                'entidad_tipo' => $entidadTipo,
                'entidad_id' => $entidadId,
                'ip' => $requestActual?->ip(),
                'user_agent' => $requestActual?->userAgent(),
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => $datosNuevos,
            ]);
        } catch (Throwable $e) {
            /*
             * La auditoría no debe romper la funcionalidad principal.
             * Si falla la bitácora, el sistema debe continuar funcionando.
             */
            report($e);
        }
    }

    public function registrarLogin(
        User $usuario,
        Request $request
    ): void {
        $this->registrar(
            accion: 'LOGIN_EXITOSO',
            modulo: 'Autenticación',
            descripcion: 'El usuario inició sesión correctamente.',
            entidadTipo: 'User',
            entidadId: $usuario->id,
            datosAnteriores: null,
            datosNuevos: [
                'email' => $usuario->email,
                'rol' => $usuario->rol,
            ],
            usuario: $usuario,
            request: $request
        );
    }

    public function registrarRegistroUsuario(
        User $usuario,
        Request $request
    ): void {
        $this->registrar(
            accion: 'REGISTRO_USUARIO',
            modulo: 'Usuarios',
            descripcion: 'Se registró un nuevo usuario en el sistema.',
            entidadTipo: 'User',
            entidadId: $usuario->id,
            datosAnteriores: null,
            datosNuevos: [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'activo' => $usuario->activo,
            ],
            usuario: $usuario,
            request: $request
        );
    }

    public function registrarCambioEstadoUsuario(
        User $admin,
        User $usuarioAfectado,
        bool $estadoAnterior,
        bool $estadoNuevo,
        Request $request
    ): void {
        $accion = $estadoNuevo
            ? 'ACTIVAR_USUARIO'
            : 'DESACTIVAR_USUARIO';

        $descripcion = $estadoNuevo
            ? 'El administrador activó una cuenta de usuario.'
            : 'El administrador desactivó una cuenta de usuario.';

        $this->registrar(
            accion: $accion,
            modulo: 'Administración',
            descripcion: $descripcion,
            entidadTipo: 'User',
            entidadId: $usuarioAfectado->id,
            datosAnteriores: [
                'activo' => $estadoAnterior,
            ],
            datosNuevos: [
                'activo' => $estadoNuevo,
                'usuario_afectado' => [
                    'id' => $usuarioAfectado->id,
                    'name' => $usuarioAfectado->name,
                    'email' => $usuarioAfectado->email,
                    'rol' => $usuarioAfectado->rol,
                ],
                'administrador' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                ],
            ],
            usuario: $admin,
            request: $request
        );
    }
    public function listarBitacorasAdmin()
{
    return Bitacora::with([
        'usuario:id,name,email,rol'
    ])
        ->select(
            'id',
            'user_id',
            'accion',
            'modulo',
            'descripcion',
            'entidad_tipo',
            'entidad_id',
            'ip',
            'user_agent',
            'datos_anteriores',
            'datos_nuevos',
            'created_at',
            'updated_at'
        )
        ->orderByDesc('created_at')
        ->limit(100)
        ->get();
}
}