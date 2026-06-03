<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articulos_sin_configurar', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100);
            $table->string('descripcion', 255)->nullable();
            $table->string('linea', 30)->nullable();
            $table->string('sucursal', 50);
            $table->string('motivo', 255)->default('Línea sin configurar');
            $table->timestamps();
            
            // Índices para búsquedas
            $table->index('clave');
            $table->index('sucursal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulos_sin_configurar');
    }
};
