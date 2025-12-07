<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class HistorialReserva extends Model
{
    protected $table = 'historial_reservas';

    protected $fillable = [
        'reserva_id',
        'user_id',
        'accion',
        'detalle',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
