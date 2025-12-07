<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recurso extends Model
{
    protected $table = 'recursos';

    protected $fillable = [
        'tipo_recurso_id',
        'nombre',
        'descripcion',
        'ubicacion',
        'capacidad',
        'disponibilidad_general',
        'estado',
    ];

    public function tipoRecurso()
    {
        return $this->belongsTo(TipoRecurso::class, 'tipo_recurso_id');
    }

    public function horarios()
    {
        return $this->hasMany(HorarioRecurso::class, 'recurso_id');
    }

    public function bloqueos()
    {
        return $this->hasMany(BloqueoRecurso::class, 'recurso_id');
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'recurso_id');
    }
}
