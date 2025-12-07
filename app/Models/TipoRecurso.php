<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoRecurso extends Model
{
    protected $table = 'tipo_recursos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    public function recursos()
    {
        return $this->hasMany(Recurso::class, 'tipo_recurso_id');
    }
}
