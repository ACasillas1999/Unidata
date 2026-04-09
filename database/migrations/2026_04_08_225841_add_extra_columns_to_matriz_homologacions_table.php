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
        Schema::table('matriz_homologacions', function (Blueprint $table) {
            $table->string('unidad_medida')->nullable();
            $table->string('linea')->nullable();
            $table->string('clasificacion')->nullable();
            $table->string('mn_usd')->nullable();
            $table->decimal('precio_lista', 15, 4)->nullable();
            $table->decimal('des_precio_venta', 15, 4)->nullable();
            $table->decimal('precio_venta', 15, 4)->nullable();
            $table->decimal('desc_precio_espec', 15, 4)->nullable();
            $table->decimal('precio_especial', 15, 4)->nullable();
            $table->decimal('desc_precio4', 15, 4)->nullable();
            $table->decimal('precio4', 15, 4)->nullable();
            $table->string('articulo_kit')->nullable();
            $table->decimal('margen_minimo', 15, 4)->nullable();
            $table->string('articulo_serie')->nullable();
            $table->string('color')->nullable();
            $table->string('protocolo')->nullable();
            $table->string('idsat')->nullable();
            $table->decimal('costo_venta', 15, 4)->nullable();
            $table->decimal('porcetaje_descuento', 15, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matriz_homologacions', function (Blueprint $table) {
            $table->dropColumn([
                'unidad_medida',
                'linea',
                'clasificacion',
                'mn_usd',
                'precio_lista',
                'des_precio_venta',
                'precio_venta',
                'desc_precio_espec',
                'precio_especial',
                'desc_precio4',
                'precio4',
                'articulo_kit',
                'margen_minimo',
                'articulo_serie',
                'color',
                'protocolo',
                'idsat',
                'costo_venta',
                'porcetaje_descuento'
            ]);
        });
    }
};
