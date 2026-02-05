<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\FinalizeReservas::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Ejecutar cada minuto para marcar reservas expiradas como finalizadas
        $schedule->command('reservas:finalizar')->everyMinute();
    }

    protected function commands()
    {
        // carga automática de comandos si se requieren
    }
}
