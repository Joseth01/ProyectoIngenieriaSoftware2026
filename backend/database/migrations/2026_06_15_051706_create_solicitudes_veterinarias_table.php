<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solicitudes_veterinarias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('animal_id')
                ->constrained('animales')
                ->cascadeOnDelete();

            $table->foreignId('finca_id')
                ->constrained('fincas')
                ->cascadeOnDelete();

            $table->foreignId('ganadero_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('veterinario_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('motivo');

            $table->enum('estado', [
                'pendiente',
                'en_revision',
                'atendida',
                'rechazada',
            ])->default('pendiente');

            $table->text('respuesta_veterinario')->nullable();

            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamp('fecha_atencion')->nullable();

            $table->timestamps();

            $table->index('animal_id');
            $table->index('finca_id');
            $table->index('ganadero_id');
            $table->index('veterinario_id');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_veterinarias');
    }
};