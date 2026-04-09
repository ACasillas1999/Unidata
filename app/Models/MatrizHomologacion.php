<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatrizHomologacion extends Model
{
    use HasFactory;

    protected $table = 'matriz_homologacions';

    protected $fillable = [
        'clave',
        'descripcion',
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
        'porcetaje_descuento',
        'en_deasa',
        'en_aiesa',
        'en_cedis',
        'en_dimegsa',
        'en_fesa',
        'en_gabsa',
        'en_ilu',
        'en_queretaro',
        'en_segsa',
        'en_tapatia',
        'en_vallarta',
        'en_washington',
    ];

    protected $casts = [
        'en_deasa' => 'boolean',
        'en_aiesa' => 'boolean',
        'en_cedis' => 'boolean',
        'en_dimegsa' => 'boolean',
        'en_fesa' => 'boolean',
        'en_gabsa' => 'boolean',
        'en_ilu' => 'boolean',
        'en_queretaro' => 'boolean',
        'en_segsa' => 'boolean',
        'en_tapatia' => 'boolean',
        'en_vallarta' => 'boolean',
        'en_washington' => 'boolean',
    ];
}
