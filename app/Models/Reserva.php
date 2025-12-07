<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'user_id',
        'recurso_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'comentarios',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id',);
    }

    public function recurso()
    {
        return $this->belongsTo(Recurso::class, 'recurso_id');
    }

    public function historial()
    {
        return $this->hasMany(HistorialReserva::class, 'reserva_id');
    }
}
