<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteCampo extends Model
{
    use HasFactory;

    protected $table = 'clientes_campos';

    protected $fillable = [
        'campo',
        'label',
        'is_required',
        'show_in_create',
        'show_in_edit',
    ];

    public $timestamps = true;
}
