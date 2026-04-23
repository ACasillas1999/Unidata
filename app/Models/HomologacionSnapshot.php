<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomologacionSnapshot extends Model
{
    protected $table = 'homologacion_snapshots';

    protected $fillable = [
        'synced_at',
        'branch_code',
        'branch_name',
        'total_activos',
        'total_inactivos',
        'total_falta',
    ];

    protected function casts(): array
    {
        return [
            'synced_at'       => 'datetime',
            'total_activos'   => 'integer',
            'total_inactivos' => 'integer',
            'total_falta'     => 'integer',
        ];
    }
}
