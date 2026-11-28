<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clientes_campos_sucursal')) {
            return;
        }
        Schema::create('clientes_campos_sucursal', function (Blueprint $table) {
            $table->id();
            $table->string('campo')->unique()->comment('Nombre del campo en la tabla clientes de la sucursal (PascalCase)');
            $table->string('label')->comment('Etiqueta visible en el formulario');
            $table->enum('tipo', ['text', 'number', 'decimal', 'date', 'boolean'])->default('text');
            $table->boolean('editable')->default(true)->comment('Si el usuario puede modificar el valor');
            $table->integer('orden')->default(0)->comment('Orden de aparición en el formulario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_campos_sucursal');
    }
};
