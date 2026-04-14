<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'        => 'Administrador',
                'slug'        => 'administrador',
                'description' => 'Acceso completo a todos los módulos y acciones del sistema.',
                'color'       => '#8b5cf6',
                'is_system'   => true,
                'permissions' => [
                    'modules' => [
                        'articulos'     => true,
                        'homologacion'  => true,
                        'estadisticas'  => true,
                        'db_master'     => true,
                        'descargas'     => true,
                        'clientes'      => true,
                        'proveedores'   => true,
                        'configuracion' => true,
                    ],
                    'actions' => [
                        'articulos_crear'      => true,
                        'articulos_subir'      => true,
                        'articulos_export'     => true,
                        'articulos_revertir'   => true,
                        'homologacion_sync'    => true,
                        'homologacion_export'  => true,
                        'db_master_sync'       => true,
                        'db_master_export'     => true,
                        'usuarios_gestionar'   => true,
                        'conexiones_gestionar' => true,
                        'roles_gestionar'      => true,
                    ],
                ],
            ],
            [
                'name'        => 'Coordinador',
                'slug'        => 'coordinador',
                'description' => 'Acceso operativo a módulos principales. Sin gestión de usuarios ni conexiones.',
                'color'       => '#0ea5e9',
                'is_system'   => true,
                'permissions' => [
                    'modules' => [
                        'articulos'     => true,
                        'homologacion'  => true,
                        'estadisticas'  => true,
                        'db_master'     => true,
                        'descargas'     => true,
                        'clientes'      => true,
                        'proveedores'   => true,
                        'configuracion' => false,
                    ],
                    'actions' => [
                        'articulos_crear'      => true,
                        'articulos_subir'      => true,
                        'articulos_export'     => true,
                        'articulos_revertir'   => true,
                        'homologacion_sync'    => true,
                        'homologacion_export'  => true,
                        'db_master_sync'       => true,
                        'db_master_export'     => true,
                        'usuarios_gestionar'   => false,
                        'conexiones_gestionar' => false,
                        'roles_gestionar'      => false,
                    ],
                ],
            ],
            [
                'name'        => 'Auxiliar',
                'slug'        => 'auxiliar',
                'description' => 'Acceso de solo lectura. Puede consultar módulos pero no sincronizar ni exportar masivamente.',
                'color'       => '#10b981',
                'is_system'   => true,
                'permissions' => [
                    'modules' => [
                        'articulos'     => true,
                        'homologacion'  => true,
                        'estadisticas'  => true,
                        'db_master'     => true,
                        'descargas'     => false,
                        'clientes'      => false,
                        'proveedores'   => false,
                        'configuracion' => false,
                    ],
                    'actions' => [
                        'articulos_crear'      => false,
                        'articulos_subir'      => false,
                        'articulos_export'     => true,
                        'articulos_revertir'   => false,
                        'homologacion_sync'    => false,
                        'homologacion_export'  => true,
                        'db_master_sync'       => false,
                        'db_master_export'     => true,
                        'usuarios_gestionar'   => false,
                        'conexiones_gestionar' => false,
                        'roles_gestionar'      => false,
                    ],
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }
}
