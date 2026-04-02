<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PortalMetricsService
{
    public function summary(): array
    {
        return [
            [
                'label' => 'Sucursales configuradas',
                'value' => $this->count('branches'),
                'hint' => 'Conexiones registradas en la base local del programa',
            ],
            [
                'label' => 'Catalogo maestro',
                'value' => $this->count('master_articles'),
                'hint' => 'Articulos homologados en la base maestra',
            ],
            [
                'label' => 'Articulos por sucursal',
                'value' => $this->count('branch_articles'),
                'hint' => 'Registros concentrados desde tiendas',
            ],
            [
                'label' => 'Pendientes de homologacion',
                'value' => $this->countWhereIn('homologation_matches', 'match_type', ['pending', 'automatic']),
                'hint' => 'Coincidencias que requieren revision o asignacion',
            ],
            [
                'label' => 'Importaciones abiertas',
                'value' => $this->countWhereIn('import_batches', 'status', ['draft', 'validated']),
                'hint' => 'Lotes con preview o validacion pendiente',
            ],
            [
                'label' => 'Lotes de sincronizacion',
                'value' => $this->count('sync_batches'),
                'hint' => 'Cambios listos o en proceso de replicacion',
            ],
            [
                'label' => 'Tiendas en reintento',
                'value' => $this->countWhereIn('sync_batch_targets', 'status', ['retry', 'failed']),
                'hint' => 'Sucursales con replica fallida o pendiente',
            ],
            [
                'label' => 'Eventos de auditoria',
                'value' => $this->count('audit_logs'),
                'hint' => 'Bitacora historica de acciones y resultados',
            ],
        ];
    }

    public function modules(): array
    {
        return [
            [
                'title' => 'Catalogo Maestro de Articulos',
                'description' => 'Alta, edicion, consulta, filtros y control del catalogo homologado unico.',
                'accent' => 'amber',
            ],
            [
                'title' => 'Homologacion de Articulos',
                'description' => 'Comparacion entre tiendas, deteccion de coincidencias y asignacion manual al maestro.',
                'accent' => 'emerald',
            ],
            [
                'title' => 'Importacion Masiva',
                'description' => 'Carga de Excel o CSV, mapeo de columnas, preview y seleccion exacta de campos a modificar.',
                'accent' => 'sky',
            ],
            [
                'title' => 'Sincronizacion',
                'description' => 'Replica unidireccional a sucursales con estatus por tienda, errores y reintentos.',
                'accent' => 'rose',
            ],
            [
                'title' => 'Historial y Auditoria',
                'description' => 'Bitacora por articulo, usuario, tienda, lote y resultado de la replicacion.',
                'accent' => 'violet',
            ],
            [
                'title' => 'Administracion',
                'description' => 'Usuarios, permisos, conexiones y reglas especificas por sucursal.',
                'accent' => 'slate',
            ],
        ];
    }

    public function flow(): array
    {
        return [
            'Se concentra la informacion de las tiendas.',
            'Se estandariza contra el catalogo maestro.',
            'Se aprueba el cambio antes de liberar la replica.',
            'Se genera un lote de sincronizacion controlado.',
            'Se replica a cada sucursal de forma unidireccional.',
            'Se guarda el resultado por tienda para seguimiento.',
            'Las fallas pasan a reintento o correccion manual.',
        ];
    }

    public function risks(): array
    {
        return [
            'No contar con un identificador unico confiable para los articulos.',
            'Sincronizar bases antes de estandarizar el catalogo maestro.',
            'Permitir cambios en el sistema central y en tiendas sin reglas claras.',
            'Realizar cargas masivas sin preview ni validacion de columnas.',
            'Asumir que todas las sucursales comparten la misma estructura interna.',
        ];
    }

    private function count(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)->count();
    }

    private function countWhereIn(string $table, string $column, array $values): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)
            ->whereIn($column, $values)
            ->count();
    }
}
