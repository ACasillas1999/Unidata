<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DbMasterSyncHistory extends Model
{
    use HasFactory;

    protected $connection = 'db_master';
    protected $table = 'Historial';

    protected $fillable = [
        'total_articulos',
    ];
}
