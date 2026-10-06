<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PowerSalesMappingCiudad extends Model
{
    protected $table = 'powersales_mapping_ciudades';

    protected $fillable = [
        'magic_cve_ciudad',
        'magic_dsc_ciudad',
        'magic_cve_estado',
        'magic_cve_pais',
        'ps_city_id',
        'ps_city_name',
        'ps_city_number',
        'ps_state_id',
    ];

    public function estado()
    {
        return $this->belongsTo(PowerSalesMappingEstado::class, 'magic_cve_estado', 'magic_clave');
    }
}
