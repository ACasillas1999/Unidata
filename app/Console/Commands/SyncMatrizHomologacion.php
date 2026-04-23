<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\HomologacionSnapshot;
use App\Models\MatrizHomologacion;
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

        // Resetear columnas de todas las sucursales activas que existen físicamente
        $physicalCols = MatrizHomologacion::getPhysicalBranchColumns();
        $colsToReset = $branches->map(fn ($b) => $this->resolveColumnName($b->code))->toArray();
        
        // Solo resetear columnas que existen tanto en el modelo como físicamente
        $validCols = array_intersect($colsToReset, $physicalCols);

        if (!empty($validCols)) {
            MatrizHomologacion::query()->update(array_fill_keys($validCols, null));
        }

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
                $connection    = $manager->connect($branch);
                $totalProcessed = 0;

                $updateColumns = [
                    $colName, 'descripcion', 'unidad_medida', 'linea', 'clasificacion', 'area',
                    'mn_usd', 'precio_lista', 'des_precio_venta', 'precio_venta', 'desc_precio_espec',
                    'precio_especial', 'desc_precio4', 'precio4', 'desc_precio_minimo', 'precio_minimo',
                    'precio_tope', 'costo_venta', 'porcetaje_descuento', 'desc_proveedor',
                    'articulo_kit', 'margen_minimo', 'articulo_serie', 'color', 'protocolo', 'idsat',
                    'clave_proveedor_1', 'costo_act_prov_1', 'clave_prov_2', 'costo_act_prov_2', 
                    'clave_prov_3', 'costo_act_prov_3', 'fecha_costo_act_p',
                    'inventario_maximo', 'inventario_minimo', 'punto_reorden', 'existencia_teorica', 'existencia_fisica',
                    'costo_promedio', 'costo_promedio_ant', 'costo_ult_compra', 'fecha_ult_compra', 
                    'costo_compra_ant', 'fecha_compra_ant', 'fecha_alta',
                    'en_promocion', 'critico', 'control_pedimentos', 'id_impuesto_sat', 'iva', 'id_tipo_factor',
                    'sustituto', 'sustituto1', 'sustituto2', 'articulo_conversion', 'conversion', 'peso', 'ubicacion', 'std_pack'
                ];

                $connection
                    ->table('articulo')
                    ->select(
                        'Clave_Articulo', 'Descripcion', 'Unidad_Medida', 'Linea', 'Clasificacion', 'Area',
                        'MN_USD', 'Precio_Lista', 'Desc_Precio_Venta', 'Precio_Venta', 'Desc_Precio_Espec',
                        'Precio_Especial', 'Desc_Precio4', 'Precio4', 'Desc_Precio_Minimo', 'Precio_Minimo',
                        'PrecioTope', 'CostoVenta', 'PorcentajeDescuento', 'Desc_Proveedor',
                        'Articulo_Kit', 'Margen_Minimo', 'Articulo_Serie', 'Color', 'Habilitado', 'Protocolo', 'IDSAT',
                        'Clave_Proveedor_1', 'Costo_Act_Prov_1', 'Clave_Prov_2', 'Costo_Act_Prov_2', 
                        'Clave_Prov_3', 'Costo_Act_Prov_3', 'Fecha_Costo_Act_P',
                        'Inventario_Maximo', 'Inventario_Minimo', 'Punto_Reorden', 'Existencia_Teorica', 'Existencia_Fisica',
                        'Costo_Promedio', 'Costo_Promedio_Ant', 'Costo_Ult_Compra', 'Fecha_Ult_Compra', 
                        'Costo_Compra_Ant', 'Fecha_Compra_Ant', 'Fecha_Alta',
                        'En_Promocion', 'Critico', 'ControlPedimentos', 'IDImpuestoSAT', 'IVA', 'IDTipoFactor',
                        'Sustituto', 'Sustituto1', 'Sustituto2', 'ArticuloConversion', 'Conversion', 'Peso', 'Ubicacion', 'StdPack'
                    )
                    ->orderBy('Clave_Articulo')
                    ->chunk(2000, function ($articles) use ($colName, $updateColumns, &$totalProcessed) {
                        $upsertData = [];
                        foreach ($articles as $art) {
                            $upsertData[] = [
                                'clave'               => $art->Clave_Articulo,
                                'descripcion'         => $art->Descripcion ?: 'SIN DESCRIPCIÓN',
                                'unidad_medida'       => $art->Unidad_Medida ?? null,
                                'linea'               => $art->Linea ?? null,
                                'clasificacion'       => $art->Clasificacion ?? null,
                                'area'                => $art->Area ?? null,
                                'mn_usd'              => $art->MN_USD ?? null,
                                'precio_lista'        => $art->Precio_Lista ?? null,
                                'des_precio_venta'    => $art->Desc_Precio_Venta ?? null,
                                'precio_venta'        => $art->Precio_Venta ?? null,
                                'desc_precio_espec'   => $art->Desc_Precio_Espec ?? null,
                                'precio_especial'     => $art->Precio_Especial ?? null,
                                'desc_precio4'        => $art->Desc_Precio4 ?? null,
                                'precio4'             => $art->Precio4 ?? null,
                                'desc_precio_minimo'  => $art->Desc_Precio_Minimo ?? null,
                                'precio_minimo'       => $art->Precio_Minimo ?? null,
                                'precio_tope'         => $art->PrecioTope ?? null,
                                'costo_venta'         => $art->CostoVenta ?? null,
                                'porcetaje_descuento' => $art->PorcentajeDescuento ?? null,
                                'desc_proveedor'      => $art->Desc_Proveedor ?? null,
                                'articulo_kit'        => $art->Articulo_Kit ?? 0,
                                'margen_minimo'       => $art->Margen_Minimo ?? null,
                                'articulo_serie'      => $art->Articulo_Serie ?? 0,
                                'color'               => $art->Color ?? null,
                                'protocolo'           => $art->Protocolo ?? null,
                                'idsat'               => $art->IDSAT ?? null,
                                'clave_proveedor_1'   => $art->Clave_Proveedor_1 ?? null,
                                'costo_act_prov_1'    => $art->Costo_Act_Prov_1 ?? null,
                                'clave_prov_2'        => $art->Clave_Prov_2 ?? null,
                                'costo_act_prov_2'    => $art->Costo_Act_Prov_2 ?? null,
                                'clave_prov_3'        => $art->Clave_Prov_3 ?? null,
                                'costo_act_prov_3'    => $art->Costo_Act_Prov_3 ?? null,
                                'fecha_costo_act_p'   => $this->sanitizeDate($art->Fecha_Costo_Act_P),
                                'inventario_maximo'   => $art->Inventario_Maximo ?? null,
                                'inventario_minimo'   => $art->Inventario_Minimo ?? null,
                                'punto_reorden'       => $art->Punto_Reorden ?? null,
                                'existencia_teorica'  => $art->Existencia_Teorica ?? null,
                                'existencia_fisica'   => $art->Existencia_Fisica ?? null,
                                'costo_promedio'      => $art->Costo_Promedio ?? null,
                                'costo_promedio_ant'  => $art->Costo_Promedio_Ant ?? null,
                                'costo_ult_compra'    => $art->Costo_Ult_Compra ?? null,
                                'fecha_ult_compra'    => $this->sanitizeDate($art->Fecha_Ult_Compra),
                                'costo_compra_ant'    => $art->Costo_Compra_Ant ?? null,
                                'fecha_compra_ant'    => $this->sanitizeDate($art->Fecha_Compra_Ant),
                                'fecha_alta'          => $this->sanitizeDate($art->Fecha_Alta),
                                'en_promocion'        => $art->En_Promocion ?? 0,
                                'critico'             => $art->Critico ?? 0,
                                'control_pedimentos'  => $art->ControlPedimentos ?? 0,
                                'id_impuesto_sat'     => $art->IDImpuestoSAT ?? null,
                                'iva'                 => $art->IVA ?? 16.00,
                                'id_tipo_factor'      => $art->IDTipoFactor ?? null,
                                'sustituto'           => $art->Sustituto ?? null,
                                'sustituto1'          => $art->Sustituto1 ?? null,
                                'sustituto2'          => $art->Sustituto2 ?? null,
                                'articulo_conversion' => $art->ArticuloConversion ?? null,
                                'conversion'          => $art->Conversion ?? null,
                                'peso'                => $art->Peso ?? null,
                                'ubicacion'           => $art->Ubicacion ?? null,
                                'std_pack'            => $art->StdPack ?? null,
                                'habilitado'          => $art->Habilitado ? 1 : 0,
                                $colName              => $art->Habilitado ? 1 : 0,
                            ];
                        }
                        foreach (array_chunk($upsertData, 500) as $chunk) {
                            MatrizHomologacion::upsert($chunk, ['clave'], $updateColumns);
                        }
                        $totalProcessed += count($articles);
                    });

                $this->info("   [OK] {$branch->name}: {$totalProcessed} registros.");

            } catch (\Throwable $e) {
                $this->error("   [ERROR] {$branch->name}: " . $e->getMessage());
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
