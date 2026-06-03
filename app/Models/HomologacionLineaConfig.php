<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomologacionLineaConfig extends Model
{
    protected $table    = 'homologacion_lineas_config';
    protected $fillable = ['linea', 'tipo', 'descripcion'];

    /** Líneas que SÍ se pasan (activos e inactivos) */
    public function scopeSiSePasa($query)
    {
        return $query->where('tipo', 'si');
    }

    /** Líneas que NO se pasan (nunca) */
    public function scopeNoSePasa($query)
    {
        return $query->where('tipo', 'no');
    }
}
