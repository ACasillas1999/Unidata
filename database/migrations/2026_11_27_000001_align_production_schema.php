<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expandir matriz_homologacions con todas las columnas faltantes
        if (Schema::hasTable('matriz_homologacions')) {
            $columnsToAdd = [
                'area'                => fn(Blueprint $t) => $t->smallInteger('area')->nullable(),
                'desc_precio_minimo'  => fn(Blueprint $t) => $t->decimal('desc_precio_minimo', 18, 4)->nullable()->default(0),
                'en_mi_sucursal'      => fn(Blueprint $t) => $t->boolean('en_mi_sucursal')->nullable(),
                'precio_minimo'       => fn(Blueprint $t) => $t->decimal('precio_minimo', 15, 4)->nullable(),
                'precio_tope'         => fn(Blueprint $t) => $t->decimal('precio_tope', 15, 4)->nullable(),
                'desc_proveedor'      => fn(Blueprint $t) => $t->decimal('desc_proveedor', 15, 4)->nullable(),
                'precio_gerente'      => fn(Blueprint $t) => $t->decimal('precio_gerente', 15, 4)->nullable(),
                'clave_proveedor_1'   => fn(Blueprint $t) => $t->integer('clave_proveedor_1')->nullable(),
                'costo_act_prov_1'    => fn(Blueprint $t) => $t->decimal('costo_act_prov_1', 15, 4)->nullable(),
                'clave_prov_2'        => fn(Blueprint $t) => $t->integer('clave_prov_2')->nullable(),
                'costo_act_prov_2'    => fn(Blueprint $t) => $t->decimal('costo_act_prov_2', 15, 4)->nullable(),
                'clave_prov_3'        => fn(Blueprint $t) => $t->integer('clave_prov_3')->nullable(),
                'costo_act_prov_3'    => fn(Blueprint $t) => $t->decimal('costo_act_prov_3', 15, 4)->nullable(),
                'fecha_costo_act_p'   => fn(Blueprint $t) => $t->date('fecha_costo_act_p')->nullable(),
                'inventario_maximo'   => fn(Blueprint $t) => $t->decimal('inventario_maximo', 15, 4)->nullable(),
                'inventario_minimo'   => fn(Blueprint $t) => $t->decimal('inventario_minimo', 15, 4)->nullable(),
                'punto_reorden'       => fn(Blueprint $t) => $t->decimal('punto_reorden', 15, 4)->nullable(),
                'ubicacion'           => fn(Blueprint $t) => $t->string('ubicacion', 20)->nullable(),
                'existencia_teorica'  => fn(Blueprint $t) => $t->decimal('existencia_teorica', 15, 4)->nullable(),
                'existencia_fisica'   => fn(Blueprint $t) => $t->decimal('existencia_fisica', 15, 4)->nullable(),
                'costo_promedio'      => fn(Blueprint $t) => $t->decimal('costo_promedio', 15, 4)->nullable(),
                'costo_promedio_ant'  => fn(Blueprint $t) => $t->decimal('costo_promedio_ant', 15, 4)->nullable(),
                'costo_ult_compra'    => fn(Blueprint $t) => $t->decimal('costo_ult_compra', 15, 4)->nullable(),
                'fecha_ult_compra'    => fn(Blueprint $t) => $t->date('fecha_ult_compra')->nullable(),
                'costo_compra_ant'    => fn(Blueprint $t) => $t->decimal('costo_compra_ant', 15, 4)->nullable(),
                'fecha_compra_ant'    => fn(Blueprint $t) => $t->date('fecha_compra_ant')->nullable(),
                'fecha_alta'          => fn(Blueprint $t) => $t->date('fecha_alta')->nullable(),
                'en_promocion'        => fn(Blueprint $t) => $t->boolean('en_promocion')->nullable()->default(0),
                'std_pack'            => fn(Blueprint $t) => $t->decimal('std_pack', 15, 4)->nullable(),
                'control_pedimentos'  => fn(Blueprint $t) => $t->boolean('control_pedimentos')->nullable()->default(0),
                'habilitado'          => fn(Blueprint $t) => $t->boolean('habilitado')->nullable()->default(1),
                'peso'                => fn(Blueprint $t) => $t->decimal('peso', 15, 4)->nullable(),
                'critico'             => fn(Blueprint $t) => $t->boolean('critico')->nullable()->default(0),
                'id_impuesto_sat'     => fn(Blueprint $t) => $t->string('id_impuesto_sat', 10)->nullable(),
                'iva'                 => fn(Blueprint $t) => $t->decimal('iva', 10, 2)->nullable()->default(16.00),
                'id_tipo_factor'      => fn(Blueprint $t) => $t->string('id_tipo_factor', 10)->nullable(),
                'sustituto'           => fn(Blueprint $t) => $t->string('sustituto', 50)->nullable(),
                'sustituto1'          => fn(Blueprint $t) => $t->string('sustituto1', 50)->nullable(),
                'sustituto2'          => fn(Blueprint $t) => $t->string('sustituto2', 50)->nullable(),
                'articulo_conversion' => fn(Blueprint $t) => $t->string('articulo_conversion', 50)->nullable(),
                'conversion'          => fn(Blueprint $t) => $t->decimal('conversion', 15, 4)->nullable(),
            ];

            $existing = Schema::getColumnListing('matriz_homologacions');
            Schema::table('matriz_homologacions', function (Blueprint $table) use ($columnsToAdd, $existing) {
                foreach ($columnsToAdd as $col => $callback) {
                    if (!in_array($col, $existing, true)) {
                        $callback($table);
                    }
                }
            });
        }

        // 2. Crear csv_historial y csv_historial_detalles si no existen
        if (!Schema::hasTable('csv_historial')) {
            Schema::create('csv_historial', function (Blueprint $table) {
                $table->increments('id');
                $table->string('archivo_nombre', 255)->nullable();
                $table->string('archivo_path', 255)->nullable();
                $table->dateTime('fecha')->useCurrent();
                $table->integer('articulos_afectados')->nullable();
                $table->text('sucursales_json')->nullable();
                $table->boolean('revertido')->default(0);
            });
        }

        if (!Schema::hasTable('csv_historial_detalles')) {
            Schema::create('csv_historial_detalles', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('historial_id')->nullable()->index();
                $table->string('clave', 50)->nullable()->index();
                $table->string('columna', 100)->nullable();
                $table->text('valor_anterior')->nullable();
                $table->text('valor_nuevo')->nullable();
                $table->string('sucursal', 20)->nullable();
            });
        }

        // 3. Agregar columna email a db_master.clientes_maestro si no existe
        try {
            if (Schema::connection('db_master')->hasTable('clientes_maestro')) {
                if (!Schema::connection('db_master')->hasColumn('clientes_maestro', 'email')) {
                    Schema::connection('db_master')->table('clientes_maestro', function (Blueprint $table) {
                        $table->string('email', 255)->nullable()->after('razon_social');
                    });
                }
            }
        } catch (\Throwable $e) {
            // Si db_master no está disponible en este momento, continuar sin detener
        }
    }

    public function down(): void
    {
        // No destructivo
    }
};

