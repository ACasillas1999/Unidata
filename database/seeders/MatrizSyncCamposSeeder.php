<?php

namespace Database\Seeders;

use App\Models\MatrizSyncCampo;
use Illuminate\Database\Seeder;

class MatrizSyncCamposSeeder extends Seeder
{
    public function run()
    {
        // Todos los campos que `SyncMatrizHomologacion` tiene harcodeados actualmente
        $allFields = [
            'clave', 'descripcion', 'unidad_medida', 'linea', 'clasificacion', 'area',
            'mn_usd', 'precio_lista', 'des_precio_venta', 'precio_venta', 'desc_precio_espec',
            'precio_especial', 'desc_precio4', 'precio4', 'desc_precio_minimo', 'precio_minimo',
            'precio_tope', 'costo_venta', 'porcetaje_descuento', 'desc_proveedor',
            'articulo_kit', 'margen_minimo', 'articulo_serie', 'color', 'protocolo', 'idsat',
            'clave_proveedor_1', 'costo_act_prov_1', 'clave_prov_2', 'costo_act_prov_2', 
            'clave_prov_3', 'costo_act_prov_3', 'fecha_costo_act_p',
            'inventario_maximo', 'inventario_minimo', 'punto_reorden', 'existencia_teorica', 'existencia_fisica',
            'costo_promedio', 'costo_promedio_ant', 'costo_ult_compra', 'fecha_ult_compra', 
            'costo_compra_ant', 'fecha_compra_ant', 'fecha_alta',
            'en_promocion', 'critico', 'control_pedimentos', 'id_impuesto_sat', 'iva', 'id_tipo_factor',
            'sustituto', 'sustituto1', 'sustituto2', 'articulo_conversion', 'conversion', 'peso', 'ubicacion', 'std_pack',
            'habilitado'
        ];

        // Los ~40 campos que pidió el usuario mantener activados
        $userActiveFields = [
            'clave',
            'descripcion',
            'unidad_medida',
            'linea',
            'clasificacion',
            'area',
            'mn_usd',
            'precio_lista',
            'des_precio_venta',
            'precio_venta',
            'desc_precio_espec',
            'precio_especial',
            'desc_precio4',
            'precio4',
            'desc_precio_minimo',
            'margen_minimo',
            'articulo_serie',
            'protocolo',
            'idsat',
            'costo_venta',
            'porcetaje_descuento',
            'precio_minimo',
            'precio_tope',
            'fecha_alta',
            'en_promocion',
            'std_pack',
            'control_pedimentos',
            'habilitado',
            'peso',
            'critico',
            'id_impuesto_sat',
            'iva',
            'id_tipo_factor',
            'sustituto',
            'sustituto1',
            'sustituto2',
            'articulo_conversion',
            'conversion'
        ];

        // Aseguramos que clave y descripcion no puedan ser deshabilitados (rompería la DB por PK)
        $requiredFields = ['clave', 'descripcion', 'habilitado'];

        foreach ($allFields as $field) {
            MatrizSyncCampo::updateOrCreate(
                ['campo' => $field],
                [
                    'is_active' => in_array($field, $userActiveFields),
                    'is_required' => in_array($field, $requiredFields)
                ]
            );
        }
    }
}
