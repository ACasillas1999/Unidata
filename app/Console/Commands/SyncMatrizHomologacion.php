<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\HomologacionSnapshot;
use App\Models\MatrizHomologacion;
use App\Models\MatrizSyncCampo;
use App\Services\BranchConnectionManager;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncMatrizHomologacion extends Command
{
    protected $signature   = 'unidata:sync-matriz';
    protected $description = 'Sincroniza todas las sucursales activas hacia la Matriz Maestra de Homologación.';

    /** Ruta del archivo de estado compartido con el controller */
    public static function statusFile(): string
    {
        return storage_path('app/sync_status.json');
    }

    private function writeStatus(string $status, string $message, int $step = 0, int $total = 0): void
    {
        $json = json_encode([
            'status'     => $status,        // running | done | error
            'message'    => $message,
            'step'       => $step,
            'total'      => $total,
            'updated_at' => time(),
        ], JSON_UNESCAPED_UNICODE);
        file_put_contents(self::statusFile(), $json, LOCK_EX);
    }

    /** 
     * Mapeo de campos entre la base de datos origen (Sucursal) y la Matriz local.
     * Facilita la mantenibilidad al centralizar los nombres de columnas.
     */
    private function getFieldMapping(): array
    {
        return [
            'clave'               => 'Clave_Articulo',
            'descripcion'         => 'Descripcion',
            'unidad_medida'       => 'Unidad_Medida',
            'linea'               => 'Linea',
            'clasificacion'       => 'Clasificacion',
            'area'                => 'Area',
            'mn_usd'              => 'MN_USD',
            'precio_lista'        => 'Precio_Lista',
            'des_precio_venta'    => 'Desc_Precio_Venta',
            'precio_venta'        => 'Precio_Venta',
            'desc_precio_espec'   => 'Desc_Precio_Espec',
            'precio_especial'     => 'Precio_Especial',
            'desc_precio4'        => 'Desc_Precio4',
            'precio4'             => 'Precio4',
            'desc_precio_minimo'  => 'Desc_Precio_Minimo',
            'precio_minimo'       => 'Precio_Minimo',
            'precio_tope'         => 'PrecioTope',
            'costo_venta'         => 'CostoVenta',
            'porcetaje_descuento' => 'PorcentajeDescuento',
            'desc_proveedor'      => 'Desc_Proveedor',
            'articulo_kit'        => 'Articulo_Kit',
            'margen_minimo'       => 'Margen_Minimo',
            'articulo_serie'      => 'Articulo_Serie',
            'color'               => 'Color',
            'protocolo'           => 'Protocolo',
            'idsat'               => 'IDSAT',
            'clave_proveedor_1'   => 'Clave_Proveedor_1',
            'costo_act_prov_1'    => 'Costo_Act_Prov_1',
            'clave_prov_2'        => 'Clave_Prov_2',
            'costo_act_prov_2'    => 'Costo_Act_Prov_2',
            'clave_prov_3'        => 'Clave_Prov_3',
            'costo_act_prov_3'    => 'Costo_Act_Prov_3',
            'fecha_costo_act_p'   => 'Fecha_Costo_Act_P',
            'inventario_maximo'   => 'Inventario_Maximo',
            'inventario_minimo'   => 'Inventario_Minimo',
            'punto_reorden'       => 'Punto_Reorden',
            'existencia_teorica'  => 'Existencia_Teorica',
            'existencia_fisica'   => 'Existencia_Fisica',
            'costo_promedio'      => 'Costo_Promedio',
            'costo_promedio_ant'  => 'Costo_Promedio_Ant',
            'costo_ult_compra'    => 'Costo_Ult_Compra',
            'fecha_ult_compra'    => 'Fecha_Ult_Compra',
            'costo_compra_ant'    => 'Costo_Compra_Ant',
            'fecha_compra_ant'    => 'Fecha_Compra_Ant',
            'fecha_alta'          => 'Fecha_Alta',
            'en_promocion'        => 'En_Promocion',
            'critico'             => 'Critico',
            'control_pedimentos'  => 'ControlPedimentos',
            'id_impuesto_sat'     => 'IDImpuestoSAT',
            'iva'                 => 'IVA',
            'id_tipo_factor'      => 'IDTipoFactor',
            'sustituto'           => 'Sustituto',
            'sustituto1'          => 'Sustituto1',
            'sustituto2'          => 'Sustituto2',
            'articulo_conversion' => 'ArticuloConversion',
            'conversion'          => 'Conversion',
            'peso'                => 'Peso',
            'ubicacion'           => 'Ubicacion',
            'std_pack'            => 'StdPack',
            'habilitado'          => 'Habilitado'
        ];
    }

    /** Resolves the branch code to the correct matrix column name */
    private function resolveColumnName(string $code): string
    {
        return MatrizHomologacion::resolveColumnName($code);
    }

    /** Sanitizes date values like '0000-00-00' or '1901-01-01' to null */
    private function sanitizeDate($date): ?string
    {
        if (!$date) return null;
        $d = (string) $date;
        if ($d === '0000-00-00' || $d === '0000-00-00 00:00:00' || $d === '1901-01-01') {
            return null;
        }
        return $d;
    }

    public function handle(BranchConnectionManager $manager)
    {
        // Solo sucursales activas
        $branches = Branch::query()->active()->orderBy('name')->get();

        if ($branches->isEmpty()) {
            $this->writeStatus('done', 'No hay sucursales activas configuradas.', 0, 0);
            $this->warn('No hay sucursales activas. Nada que sincronizar.');
            return;
        }

        $total = $branches->count();
        $step  = 0;

        $this->writeStatus('running', 'Iniciando sincronización...', 0, $total);
        $this->info("Iniciando sincronización ({$total} sucursales activas)...");

        // ── LIMPIEZA INICIAL ─────────────────────────────────────────────
        // 1. Resetear marcas de existencia en sucursales
        $physicalCols = MatrizHomologacion::getPhysicalBranchColumns();
        $colsToReset  = $branches->map(fn ($b) => $this->resolveColumnName($b->code))->toArray();
        $validCols    = array_intersect($colsToReset, $physicalCols);
        
        if (!empty($validCols)) {
            MatrizHomologacion::query()->update(array_fill_keys($validCols, null));
        }

        // 2. Limpiar campos que NO están seleccionados (según requerimiento del usuario)
        $inactiveCampos = MatrizSyncCampo::where('is_active', false)->pluck('campo')->toArray();
        if (!empty($inactiveCampos)) {
            $this->info("   [CLEANUP] Limpiando campos inactivos en la matriz...");
            MatrizHomologacion::query()->update(array_fill_keys($inactiveCampos, null));
        }
        // ─────────────────────────────────────────────────────────────────

        $fieldMap = $this->getFieldMapping();
        $activeCampos = MatrizSyncCampo::where('is_active', true)->pluck('campo')->toArray();

        foreach ($branches as $branch) {
            // Revisar si el usuario canceló la sincronización
            if (file_exists(self::statusFile())) {
                $statusData = json_decode(file_get_contents(self::statusFile()), true) ?? [];
                if (($statusData['status'] ?? '') === 'cancelled') {
                    $this->warn('Sincronización cancelada por el usuario (abortado).');
                    return; // Detiene el proceso limpiamente
                }
            }

            $step++;
            $colName = $this->resolveColumnName($branch->code);

            // Verificar que la columna existe en el modelo antes de intentar escribirla
            if (!in_array($colName, MatrizHomologacion::make()->getFillable())) {
                $this->warn("   [SKIP] {$branch->name}: columna \"{$colName}\" no existe en la matriz. Omitiendo.");
                continue;
            }

            $this->writeStatus('running', "Sincronizando {$branch->name} ({$step}/{$total})...", $step, $total);
            $this->info("-> Conectando a [{$branch->name} / {$branch->code}]...");

            try {
                $connection = $manager->connect($branch);
                $totalProcessed = 0;

                // Definir qué columnas vamos a actualizar en el upsert
                $updateColumns = array_unique(array_merge($activeCampos, ['clave', 'descripcion', 'habilitado', $colName]));
                
                // Construir el select dinámicamente basado en los campos activos
                $sourceSelect = [];
                foreach ($updateColumns as $col) {
                    if (isset($fieldMap[$col])) {
                        $sourceSelect[] = $fieldMap[$col];
                    }
                }
                // Asegurar campos base si no estaban
                $sourceSelect = array_unique(array_merge($sourceSelect, ['Clave_Articulo', 'Habilitado']));

                $connection
                    ->table('articulo')
                    ->select($sourceSelect)
                    ->orderBy('Clave_Articulo')
                    ->chunk(2000, function ($articles) use ($colName, $updateColumns, $fieldMap, &$totalProcessed) {
                        $upsertData = [];
                        foreach ($articles as $art) {
                            $row = [];
                            
                            // Mapeo dinámico
                            foreach ($updateColumns as $matrixCol) {
                                if ($matrixCol === $colName) {
                                    $row[$matrixCol] = ($art->Habilitado ?? 0) ? 1 : 0;
                                    continue;
                                }

                                $sourceCol = $fieldMap[$matrixCol] ?? null;
                                if (!$sourceCol) continue;

                                $val = $art->{$sourceCol} ?? null;

                                // Lógica especial para ciertos campos
                                if (str_starts_with($matrixCol, 'fecha_')) {
                                    $val = $this->sanitizeDate($val);
                                } elseif ($matrixCol === 'descripcion') {
                                    $val = $val ?: 'SIN DESCRIPCIÓN';
                                } elseif ($matrixCol === 'iva' && $val === null) {
                                    $val = 16.00;
                                } elseif (in_array($matrixCol, ['articulo_kit', 'articulo_serie', 'en_promocion', 'critico', 'control_pedimentos'])) {
                                    $val = $val ? 1 : 0;
                                } elseif ($matrixCol === 'habilitado') {
                                    $val = $val ? 1 : 0;
                                }

                                $row[$matrixCol] = $val;
                            }
                            
                            $upsertData[] = $row;
                        }

                        foreach (array_chunk($upsertData, 500) as $chunk) {
                            MatrizHomologacion::upsert($chunk, ['clave'], $updateColumns);
                        }
                        $totalProcessed += count($articles);
                    });

                $this->info("   [OK] {$branch->name}: {$totalProcessed} registros.");

            } catch (\Throwable $e) {
                $this->error("   [ERROR] {$branch->name}: " . $e->getMessage());
                // Registrar el error detallado para depuración
                file_put_contents(storage_path('logs/sync_error_detail.log'), $e->getMessage() . PHP_EOL . $e->getTraceAsString(), FILE_APPEND);
            }
        }

        
        // Eliminar artículos que no están en ninguna sucursal (todas las columnas son NULL)
        if (!empty($validCols)) {
            $queryDelete = MatrizHomologacion::query();
            foreach ($validCols as $col) {
                $queryDelete->whereNull($col);
            }
            $deleted = $queryDelete->delete();
            if ($deleted > 0) {
                $this->info("   [CLEANUP] Se eliminaron {$deleted} registros que ya no existen en ninguna sucursal.");
            }
        }

        // ── Guardar snapshot de conteos por sucursal ───────────────────
        $this->guardarSnapshot($branches);

        $this->writeStatus('done', '¡Sincronización completada exitosamente!', $total, $total);
        $this->info('¡Sincronización Finalizada con Éxito!');
    }

    /**
     * Persiste un snapshot con el conteo de artículos activos/inactivos/falta
     * por sucursal en la tabla homologacion_snapshots.
     * Se llama una vez al finalizar cada sync exitosa.
     */
    private function guardarSnapshot(Collection $branches): void
    {
        $now          = now();
        $physicalCols = MatrizHomologacion::getPhysicalBranchColumns();

        foreach ($branches as $branch) {
            $col = $this->resolveColumnName($branch->code);

            if (!in_array($col, $physicalCols)) {
                continue;
            }

            $activos   = MatrizHomologacion::where($col, 1)->count();
            $inactivos = MatrizHomologacion::where($col, 0)->count();
            $falta     = MatrizHomologacion::whereNull($col)->count();

            HomologacionSnapshot::create([
                'synced_at'       => $now,
                'branch_code'     => $branch->code,
                'branch_name'     => $branch->name,
                'total_activos'   => $activos,
                'total_inactivos' => $inactivos,
                'total_falta'     => $falta,
            ]);

            $this->info("   [SNAPSHOT] {$branch->name}: {$activos} activos | {$inactivos} inactivos | {$falta} falta");
        }
    }
}
