<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PowerSalesMappingEstado extends Model
{
    protected $table = 'powersales_mapping_estados';

    protected $fillable = [
        'magic_clave',
        'magic_descripcion',
        'ps_state_id',
        'ps_state_name',
        'ps_state_number',
        'ps_states_col',
    ];

    public function ciudades()
    {
        return $this->hasMany(PowerSalesMappingCiudad::class, 'magic_cve_estado', 'magic_clave');
    }
}
