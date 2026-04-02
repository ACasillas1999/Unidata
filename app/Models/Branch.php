<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'code',
        'name',
        'db_host',
        'db_port',
        'db_user',
        'db_password',
        'db_database',
        'status',
        'last_connection_check',
        'connection_status',
    ];

    protected $hidden = [
        'db_password',
    ];

    protected function casts(): array
    {
        return [
            'db_port' => 'integer',
            'last_connection_check' => 'datetime',
        ];
    }

    public function getConnectionName(): ?string
    {
        return config('branch-connections.local_connection', config('database.default'));
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', config('branch-connections.active_status', 'active'));
    }
}
