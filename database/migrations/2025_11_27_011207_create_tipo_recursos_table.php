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
        Schema::create('tipo_recursos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 70);
            $table->string('descripcion', 255)->nullable();
            $table->tinyInteger('estado')->default(1); // 1 = activo, 0 = inactivo, 2 = eliminado
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_recursos');
    }
};
