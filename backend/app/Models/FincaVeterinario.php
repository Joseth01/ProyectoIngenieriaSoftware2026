<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FincaVeterinario extends Model
{
    protected $table = 'finca_veterinario';

    protected $fillable = [
        'finca_id',
        'veterinario_id',
        'asignado_por',
        'activo',
        'fecha_asignacion',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_asignacion' => 'datetime',
    ];

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class, 'finca_id');
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    public function asignador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }
}