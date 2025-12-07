<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioRecurso extends Model
{
    protected $table = 'horario_recursos';

    protected $fillable = [
        'recurso_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'estado',
    ];

    public function recurso()
    {
        return $this->belongsTo(Recurso::class, 'recurso_id');
    }
}
