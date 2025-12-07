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
        Schema::create('bloqueo_recursos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('recurso_id')
                ->constrained('recursos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->string('motivo', 255);

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
        Schema::dropIfExists('bloqueo_recursos');
    }
};
