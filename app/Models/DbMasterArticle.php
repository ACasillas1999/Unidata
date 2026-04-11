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
        'clave', 'descripcion', 'unidad_medida', 'linea', 'clasificacion',
        'mn_usd', 'precio_lista', 'des_precio_venta', 'precio_venta',
        'desc_precio_espec', 'precio_especial', 'desc_precio4', 'precio4',
        'articulo_kit', 'margen_minimo', 'articulo_serie', 'color',
        'protocolo', 'idsat', 'costo_venta', 'porcetaje_descuento',
    ];
}
