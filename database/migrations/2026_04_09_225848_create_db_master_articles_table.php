<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    protected $connection = 'db_master';

    public function up(): void
    {
        Schema::connection('db_master')->create('Articulos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->string('unidad_medida', 50)->nullable();
            $table->string('linea', 150)->nullable();
            $table->string('clasificacion', 150)->nullable();
            $table->boolean('mn_usd')->nullable();
            $table->decimal('precio_lista', 15, 4)->nullable();
            $table->decimal('des_precio_venta', 15, 4)->nullable();
            $table->decimal('precio_venta', 15, 4)->nullable();
            $table->decimal('desc_precio_espec', 15, 4)->nullable();
            $table->decimal('precio_especial', 15, 4)->nullable();
            $table->decimal('desc_precio4', 15, 4)->nullable();
            $table->decimal('precio4', 15, 4)->nullable();
            $table->boolean('articulo_kit')->nullable();
            $table->decimal('margen_minimo', 5, 2)->nullable();
            $table->boolean('articulo_serie')->nullable();
            $table->string('color', 50)->nullable();
            $table->string('protocolo', 50)->nullable();
            $table->string('idsat', 50)->nullable();
            $table->decimal('costo_venta', 15, 4)->nullable();
            $table->decimal('porcetaje_descuento', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('db_master')->dropIfExists('Articulos');
    }
};
