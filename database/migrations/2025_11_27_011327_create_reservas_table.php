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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('recurso_id')
                ->constrained('recursos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');

            // estado de negocio: activa, cancelada, finalizada, etc.
            $table->string('estado', 25)->default('activa');

            $table->text('comentarios')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
