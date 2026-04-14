<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_system',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    /** Usuarios que tienen asignado este rol. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'name');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Comprueba si el rol tiene un permiso dado.
     *
     * @param  string  $key  p.ej. "modules.articulos" o "actions.homologacion_sync"
     */
    public function hasPermission(string $key): bool
    {
        $perms = $this->permissions ?? [];
        [$section, $item] = array_pad(explode('.', $key, 2), 2, null);

        if ($item === null) {
            return !empty($perms[$section]);
        }

        return !empty($perms[$section][$item]);
    }

    // ── Defaults ───────────────────────────────────────────────────────────────

    public static function defaultPermissions(): array
    {
        return [
            'modules' => [
                'articulos'     => false,
                'homologacion'  => false,
                'estadisticas'  => false,
                'db_master'     => false,
                'descargas'     => false,
                'clientes'      => false,
                'proveedores'   => false,
                'configuracion' => false,
            ],
            'actions' => [
                'articulos_crear'        => false,
                'articulos_subir'        => false,
                'articulos_export'       => false,
                'articulos_revertir'     => false,
                'homologacion_sync'      => false,
                'homologacion_export'    => false,
                'db_master_sync'         => false,
                'db_master_export'       => false,
                'usuarios_gestionar'     => false,
                'conexiones_gestionar'   => false,
                'roles_gestionar'        => false,
            ],
        ];
    }
}
