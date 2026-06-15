<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerfilVeterinario extends Model
{
    protected $table = 'perfiles_veterinarios';

    protected $fillable = [
        'user_id',
        'codigo_colegiado',
        'telefono_urgencias',
        'especialidad',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}