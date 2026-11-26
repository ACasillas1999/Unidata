<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permiso nuevo actions.clientes_homologar (Clientes → Homologación de clientes).
 * Se activa solo en el rol Administrador; los demás roles lo reciben apagado.
 * Solo agrega la llave: no toca los permisos que cada rol ya tiene.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('roles')->get(['id', 'slug', 'permissions']) as $role) {
            $perms = json_decode((string) $role->permissions, true) ?: [];
            $perms['actions'] ??= [];
            if (array_key_exists('clientes_homologar', $perms['actions'])) {
                continue;
            }
            $perms['actions']['clientes_homologar'] = $role->slug === 'administrador';
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($perms)]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->get(['id', 'permissions']) as $role) {
            $perms = json_decode((string) $role->permissions, true) ?: [];
            if (!isset($perms['actions']) || !array_key_exists('clientes_homologar', $perms['actions'])) {
                continue;
            }
            unset($perms['actions']['clientes_homologar']);
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($perms)]);
        }
    }
};
