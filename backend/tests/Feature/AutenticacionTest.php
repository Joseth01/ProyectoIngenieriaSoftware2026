<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_puede_registrarse(): void
{
    $response = $this->postJson('/api/usuarios/registro', [
        'name'     => 'Test Usuario',
        'email'    => 'test@bovweight.com',
        'password' => 'password123',
        'rol'      => 'ganadero',
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('exito', true)
             ->assertJsonStructure([
                 'datos' => ['id', 'name', 'email']
             ]);
}

   public function test_usuario_puede_hacer_login(): void
{
    User::create([
        'name'     => 'Test Usuario',
        'email'    => 'test@bovweight.com',
        'password' => 'password123',
        'rol'      => 'ganadero',
    ]);

    $response = $this->postJson('/api/usuarios/login', [
        'email'    => 'test@bovweight.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('exito', true)
             ->assertJsonStructure([
                 'datos' => ['id', 'name', 'email']
             ]);
}

    public function test_login_falla_con_credenciales_incorrectas(): void
    {
        $response = $this->postJson('/api/usuarios/login', [
            'email'    => 'noexiste@bovweight.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('exito', false);
    }

}
