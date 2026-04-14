<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'db_master';

    public function up(): void
    {
        Schema::connection('db_master')->table('Articulos', function (Blueprint $table) {
            // Logística y General
            $table->smallInteger('area')->nullable()->after('clasificacion');
            $table->decimal('peso', 15, 4)->nullable()->after('idsat');
            $table->string('ubicacion', 20)->nullable()->after('idsat');
            $table->decimal('std_pack', 15, 4)->nullable()->after('idsat');
            
            // Precios y Costos adicionales
            $table->decimal('desc_precio_minimo', 15, 4)->nullable()->after('precio4');
            $table->decimal('precio_minimo', 15, 4)->nullable()->after('precio4');
            $table->decimal('desc_proveedor', 15, 4)->nullable()->after('costo_venta');
            $table->decimal('precio_tope', 15, 4)->nullable()->after('precio4');
            
            // Proveedores
            $table->integer('clave_proveedor_1')->nullable();
            $table->decimal('costo_act_prov_1', 15, 4)->nullable();
            $table->integer('clave_prov_2')->nullable();
            $table->decimal('costo_act_prov_2', 15, 4)->nullable();
            $table->integer('clave_prov_3')->nullable();
            $table->decimal('costo_act_prov_3', 15, 4)->nullable();
            $table->date('fecha_costo_act_p')->nullable();
            
            // Inventarios
            $table->decimal('inventario_maximo', 15, 4)->nullable();
            $table->decimal('inventario_minimo', 15, 4)->nullable();
            $table->decimal('punto_reorden', 15, 4)->nullable();
            $table->decimal('existencia_teorica', 15, 4)->nullable();
            $table->decimal('existencia_fisica', 15, 4)->nullable();
            
            // Costos Históricos
            $table->decimal('costo_promedio', 15, 4)->nullable();
            $table->decimal('costo_promedio_ant', 15, 4)->nullable();
            $table->decimal('costo_ult_compra', 15, 4)->nullable();
            $table->date('fecha_ult_compra')->nullable();
            $table->decimal('costo_compra_ant', 15, 4)->nullable();
            $table->date('fecha_compra_ant')->nullable();
            $table->date('fecha_alta')->nullable();
            
            // Banderas y SAT
            $table->boolean('en_promocion')->nullable()->default(0);
            $table->boolean('critico')->nullable()->default(0);
            $table->boolean('control_pedimentos')->nullable()->default(0);
            $table->string('id_impuesto_sat', 10)->nullable();
            $table->decimal('iva', 10, 2)->nullable()->default(16.00);
            $table->string('id_tipo_factor', 10)->nullable();
            
            // Relaciones
            $table->string('sustituto', 50)->nullable();
            $table->string('sustituto1', 50)->nullable();
            $table->string('sustituto2', 50)->nullable();
            $table->string('articulo_conversion', 50)->nullable();
            $table->decimal('conversion', 15, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('db_master')->table('Articulos', function (Blueprint $table) {
            $table->dropColumn([
                'area', 'peso', 'ubicacion', 'std_pack',
                'desc_precio_minimo', 'precio_minimo', 'desc_proveedor', 'precio_tope',
                'clave_proveedor_1', 'costo_act_prov_1', 'clave_prov_2', 'costo_act_prov_2', 'clave_prov_3', 'costo_act_prov_3', 'fecha_costo_act_p',
                'inventario_maximo', 'inventario_minimo', 'punto_reorden', 'existencia_teorica', 'existencia_fisica',
                'costo_promedio', 'costo_promedio_ant', 'costo_ult_compra', 'fecha_ult_compra', 'costo_compra_ant', 'fecha_compra_ant', 'fecha_alta',
                'en_promocion', 'critico', 'control_pedimentos', 'id_impuesto_sat', 'iva', 'id_tipo_factor',
                'sustituto', 'sustituto1', 'sustituto2', 'articulo_conversion', 'conversion'
            ]);
        });
    }
};
