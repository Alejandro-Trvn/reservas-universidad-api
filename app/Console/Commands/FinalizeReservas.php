<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reserva;
use App\Models\HistorialReserva;
use App\Models\Notificacion;
use App\Models\User;
use Carbon\Carbon;

class FinalizeReservas extends Command
{
    protected $signature = 'reservas:finalizar';
    protected $description = 'Marcar reservas expiradas como finalizadas y notificar a usuarios y admins';

    public function handle()
    {
        $now = Carbon::now();

        $reservas = Reserva::where('estado', 'activa')
            ->where('fecha_fin', '<', $now)
            ->get();

        if ($reservas->isEmpty()) {
            $this->info('No hay reservas para finalizar.');
            return 0;
        }

        // Obtener ids de administradores
        $adminIds = User::whereHas('role', function ($q) {
            $q->where('nombre', 'admin');
        })->pluck('id')->toArray();

        // Actor para el historial: intentar usar un admin si existe
        $actor = User::whereHas('role', function ($q) {
            $q->where('nombre', 'admin');
        })->first();

        foreach ($reservas as $reserva) {
            $reserva->estado = 'finalizada';
            $reserva->save();

            // Registrar en historial si hay un actor disponible
            if ($actor) {
                HistorialReserva::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $actor->id,
                    'accion' => 'finalizada',
                    'detalle' => "Reserva finalizada automáticamente por el sistema al expirar fecha_fin ({$reserva->fecha_fin}).",
                ]);
            }

            // Notificar al dueño
            if ($reserva->user_id) {
                Notificacion::create([
                    'user_id' => $reserva->user_id,
                    'tipo' => 'reserva_finalizada',
                    'titulo' => 'Reserva finalizada',
                    'mensaje' => "Tu reserva del recurso {$reserva->recurso->nombre} finalizó el {$reserva->fecha_fin} y ha sido marcada como finalizada.",
                ]);
            }

            // Notificar a admins
            if (!empty($adminIds)) {
                foreach ($adminIds as $adminId) {
                    Notificacion::create([
                        'user_id' => $adminId,
                        'tipo' => 'reserva_finalizada',
                        'titulo' => 'Reserva finalizada automáticamente',
                        'mensaje' => "La reserva (ID: {$reserva->id}) del recurso {$reserva->recurso->nombre} ha sido finalizada automáticamente.",
                    ]);
                }
            }
        }

        $this->info('Procesadas ' . $reservas->count() . ' reservas finalizadas.');

        return 0;
    }
}
