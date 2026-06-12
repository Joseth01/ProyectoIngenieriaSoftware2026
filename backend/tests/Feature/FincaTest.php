<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Finca;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FincaTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioConToken(): array
    {
        $user = User::create([
            'name'     => 'Ganadero Test',
            'email'    => 'ganadero@test.com',
            'password' => 'password123',
            'rol'      => 'ganadero',
        ]);
        $token = $user->createToken('test')->plainTextToken;
        return ['user' => $user, 'token' => $token];
    }

    public function test_puede_crear_finca(): void
    {
        ['user' => $user, 'token' => $token] = $this->crearUsuarioConToken();

        $response = $this->withToken($token)->postJson('/api/fincas', [
            'nombre'    => 'Finca Test',
            'ubicacion' => 'Guanacaste',
            'user_id'   => $user->id,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('exito', true)
                 ->assertJsonPath('datos.nombre', 'Finca Test');
    }

    public function test_puede_listar_fincas(): void
    {
        ['user' => $user, 'token' => $token] = $this->crearUsuarioConToken();

        Finca::create([
            'nombre'    => 'Finca Lista',
            'ubicacion' => 'Liberia',
            'user_id'   => $user->id,
        ]);

        $response = $this->withToken($token)->getJson('/api/fincas');

        $response->assertStatus(200)
                 ->assertJsonPath('exito', true);
    }

    public function test_puede_obtener_finca_por_id(): void
    {
        ['user' => $user, 'token' => $token] = $this->crearUsuarioConToken();

        $finca = Finca::create([
            'nombre'    => 'Finca Detalle',
            'ubicacion' => 'Nicoya',
            'user_id'   => $user->id,
        ]);

        $response = $this->withToken($token)->getJson("/api/fincas/{$finca->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('datos.nombre', 'Finca Detalle');
    }
}