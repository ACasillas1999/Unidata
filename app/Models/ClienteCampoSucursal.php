<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteCampoSucursal extends Model
{
    use HasFactory;

    protected $table = 'clientes_campos_sucursal';

    protected $fillable = [
        'campo',
        'label',
        'tipo',
        'editable',
        'orden',
    ];

    protected $casts = [
        'editable' => 'boolean',
        'orden'    => 'integer',
    ];

    public $timestamps = true;
}
