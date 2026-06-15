<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudVeterinaria extends Model
{
    protected $table = 'solicitudes_veterinarias';

    protected $fillable = [
        'animal_id',
        'finca_id',
        'ganadero_id',
        'veterinario_id',
        'motivo',
        'estado',
        'respuesta_veterinario',
        'fecha_solicitud',
        'fecha_atencion',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_atencion' => 'datetime',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class, 'finca_id');
    }

    public function ganadero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ganadero_id');
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }
}