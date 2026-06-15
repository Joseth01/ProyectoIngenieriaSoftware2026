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
        Schema::create('finca_veterinario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('finca_id')
                ->constrained('fincas')
                ->cascadeOnDelete();

            $table->foreignId('veterinario_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('asignado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('activo')->default(true);
            $table->timestamp('fecha_asignacion')->useCurrent();

            $table->timestamps();

            $table->unique([
                'finca_id',
                'veterinario_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finca_veterinario');
    }
};