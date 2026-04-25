<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatrizSyncCampo extends Model
{
    use HasFactory;

    protected $table = 'matriz_sync_campos';

    protected $fillable = [
        'campo',
        'is_active',
        'is_required'
    ];
    
    public $timestamps = true;
}
