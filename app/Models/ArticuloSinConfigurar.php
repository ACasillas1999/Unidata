<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticuloSinConfigurar extends Model
{
    protected $table = 'articulos_sin_configurar';
    protected $fillable = ['clave', 'descripcion', 'linea', 'sucursal', 'motivo'];
}
