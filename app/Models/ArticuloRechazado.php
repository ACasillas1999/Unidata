<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticuloRechazado extends Model
{
    protected $table = 'articulos_rechazados';
    protected $fillable = ['clave', 'descripcion', 'linea', 'sucursal', 'motivo'];
}
