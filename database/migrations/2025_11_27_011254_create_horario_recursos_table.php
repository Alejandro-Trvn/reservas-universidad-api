<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('horario_recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurso_id')
                ->constrained('recursos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // 0 = domingo, 1 = lunes, ... 6 = sábado
            $table->tinyInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            // 1 = activo, 0 = inactivo
            $table->tinyInteger('estado')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horario_recursos');
    }
};
