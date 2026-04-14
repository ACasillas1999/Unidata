<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DbMasterArticle extends Model
{
    use HasFactory;

    protected $connection = 'db_master';
    protected $table = 'Articulos';
    
    protected $fillable = [
        'clave', 'descripcion', 'unidad_medida', 'linea', 'clasificacion', 'area',
        'mn_usd', 'precio_lista', 'des_precio_venta', 'precio_venta',
        'desc_precio_espec', 'precio_especial', 'desc_precio4', 'precio4',
        'desc_precio_minimo', 'precio_minimo', 'precio_tope', 'desc_proveedor',
        'articulo_kit', 'margen_minimo', 'articulo_serie', 'color',
        'protocolo', 'idsat', 'costo_venta', 'porcetaje_descuento',
        'clave_proveedor_1', 'costo_act_prov_1', 'clave_prov_2', 'costo_act_prov_2', 'clave_prov_3', 'costo_act_prov_3', 'fecha_costo_act_p',
        'inventario_maximo', 'inventario_minimo', 'punto_reorden', 'existencia_teorica', 'existencia_fisica',
        'costo_promedio', 'costo_promedio_ant', 'costo_ult_compra', 'fecha_ult_compra', 'costo_compra_ant', 'fecha_compra_ant', 'fecha_alta',
        'en_promocion', 'critico', 'control_pedimentos', 'id_impuesto_sat', 'iva', 'id_tipo_factor',
        'sustituto', 'sustituto1', 'sustituto2', 'articulo_conversion', 'conversion', 'peso', 'ubicacion', 'std_pack',
        'habilitado'
    ];
}
