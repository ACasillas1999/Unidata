<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homologacion_lineas_config', function (Blueprint $table) {
            $table->id();
            $table->string('linea', 30)->unique();
            $table->enum('tipo', ['si', 'no']); // 'si' = se pasa, 'no' = no se pasa
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homologacion_lineas_config');
    }
};
