<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Finca;
use App\Models\Raza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalTest extends TestCase
{
    use RefreshDatabase;

    private function setup_datos(): array
    {
        $user = User::create([
            'name'     => 'Ganadero Test',
            'email'    => 'ganadero@test.com',
            'password' => 'password123',
            'rol'      => 'ganadero',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $finca = Finca::create([
            'nombre'    => 'Finca Test',
            'ubicacion' => 'Guanacaste',
            'user_id'   => $user->id,
        ]);

        $raza = Raza::create(['nombre' => 'Brahman']);

        return ['user' => $user, 'token' => $token, 'finca' => $finca, 'raza' => $raza];
    }

    public function test_puede_crear_animal(): void
    {
        ['token' => $token, 'finca' => $finca, 'raza' => $raza] = $this->setup_datos();

        $response = $this->withToken($token)->postJson('/api/animales', [
            'numero_arete'     => '001-2026',
            'nombre'           => 'Canela',
            'raza_id'          => $raza->id,
            'nombre_raza'      => 'brahman',
            'fecha_nacimiento' => '2022-01-15',
            'finca_id'         => $finca->id,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('exito', true)
                 ->assertJsonPath('datos.nombre', 'Canela');
    }

    public function test_puede_listar_animales(): void
    {
        ['token' => $token] = $this->setup_datos();

        $response = $this->withToken($token)->getJson('/api/animales');

        $response->assertStatus(200)
                 ->assertJsonPath('exito', true);
    }

    public function test_no_puede_crear_animal_sin_arete(): void
    {
        ['token' => $token, 'finca' => $finca, 'raza' => $raza] = $this->setup_datos();

        $response = $this->withToken($token)->postJson('/api/animales', [
            'nombre'           => 'Sin Arete',
            'raza_id'          => $raza->id,
            'nombre_raza'      => 'brahman',
            'fecha_nacimiento' => '2022-01-15',
            'finca_id'         => $finca->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_no_puede_crear_animal_con_arete_formato_invalido(): void
    {
        ['token' => $token, 'finca' => $finca, 'raza' => $raza] = $this->setup_datos();

        // Arete sin dígitos: no cumple el formato SENASA
        $response = $this->withToken($token)->postJson('/api/animales', [
            'numero_arete'     => 'VACA-AZUL',
            'nombre'           => 'Formato Malo',
            'raza_id'          => $raza->id,
            'nombre_raza'      => 'brahman',
            'fecha_nacimiento' => '2022-01-15',
            'finca_id'         => $finca->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('numero_arete');
    }

    public function test_acepta_arete_con_prefijo_de_pais(): void
    {
        ['token' => $token, 'finca' => $finca, 'raza' => $raza] = $this->setup_datos();

        $response = $this->withToken($token)->postJson('/api/animales', [
            'numero_arete'     => 'CR005',
            'nombre'           => 'Aurora',
            'raza_id'          => $raza->id,
            'nombre_raza'      => 'brahman',
            'fecha_nacimiento' => '2022-01-15',
            'finca_id'         => $finca->id,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('exito', true);
    }
}