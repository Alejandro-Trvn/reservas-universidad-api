<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloqueoRecurso extends Model
{
    protected $table = 'bloqueo_recursos';

    protected $fillable = [
        'recurso_id',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'estado',
    ];

    public function recurso()
    {
        return $this->belongsTo(Recurso::class, 'recurso_id');
    }
}
