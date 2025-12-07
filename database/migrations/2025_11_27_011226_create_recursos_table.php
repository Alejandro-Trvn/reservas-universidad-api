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
        Schema::create('recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_recurso_id')
                ->constrained('tipo_recursos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->string('ubicacion', 150)->nullable();
            $table->integer('capacidad')->nullable();

            // disponibilidad general: 1 = se puede reservar, 0 = no
            $table->boolean('disponibilidad_general')->default(true);

            // 1 = activo, 0 = inactivo, 2 = eliminado
            $table->tinyInteger('estado')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recursos');
    }
};
