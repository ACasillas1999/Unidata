<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\MatrizHomologacion;
use App\Services\BranchConnectionManager;
use Illuminate\Console\Command;

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
        file_put_contents(self::statusFile(), json_encode([
            'status'     => $status,        // running | done | error
            'message'    => $message,
            'step'       => $step,
            'total'      => $total,
            'updated_at' => time(),
        ], JSON_UNESCAPED_UNICODE));
    }

    /** Resolves the branch code to the correct matrix column name */
    private function resolveColumnName(string $code): string
    {
        return MatrizHomologacion::resolveColumnName($code);
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
                    $colName,
                    'descripcion',
                    'unidad_medida',
                    'linea',
                    'clasificacion',
                    'mn_usd',
                    'precio_lista',
                    'des_precio_venta',
                    'precio_venta',
                    'desc_precio_espec',
                    'precio_especial',
                    'desc_precio4',
                    'precio4',
                    'articulo_kit',
                    'margen_minimo',
                    'articulo_serie',
                    'color',
                    'protocolo',
                    'idsat',
                    'costo_venta',
                    'porcetaje_descuento'
                ];

                $connection
                    ->table('articulo')
                    ->select(
                        'Clave_Articulo', 'Descripcion', 'Unidad_Medida', 'Linea', 'Clasificacion',
                        'MN_USD', 'Precio_Lista', 'Desc_Precio_Venta', 'Precio_Venta', 'Desc_Precio_Espec',
                        'Precio_Especial', 'Desc_Precio4', 'Precio4', 'Articulo_Kit', 'Margen_Minimo',
                        'Articulo_Serie', 'Color', 'Habilitado', 'Protocolo', 'IDSAT', 'CostoVenta', 'PorcentajeDescuento'
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
                                'clasificacion'       => $art->Clasificacion ?? ($art->Clasificación ?? null),
                                'mn_usd'              => $art->MN_USD ?? null,
                                'precio_lista'        => $art->Precio_Lista ?? null,
                                'des_precio_venta'    => $art->Desc_Precio_Venta ?? null,
                                'precio_venta'        => $art->Precio_Venta ?? null,
                                'desc_precio_espec'   => $art->Desc_Precio_Espec ?? null,
                                'precio_especial'     => $art->Precio_Especial ?? null,
                                'desc_precio4'        => $art->Desc_Precio4 ?? null,
                                'precio4'             => $art->Precio4 ?? null,
                                'articulo_kit'        => $art->Articulo_Kit ?? null,
                                'margen_minimo'       => $art->Margen_Minimo ?? null,
                                'articulo_serie'      => $art->Articulo_Serie ?? null,
                                'color'               => $art->Color ?? null,
                                'protocolo'           => $art->Protocolo ?? null,
                                'idsat'               => $art->IDSAT ?? null,
                                'costo_venta'         => $art->CostoVenta ?? null,
                                'porcetaje_descuento' => $art->PorcentajeDescuento ?? null,
                                $colName              => $art->Habilitado ? 1 : 0,
                            ];
                        }
                        MatrizHomologacion::upsert($upsertData, ['clave'], $updateColumns);
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

        $this->writeStatus('done', '¡Sincronización completada exitosamente!', $total, $total);
        $this->info('¡Sincronización Finalizada con Éxito!');
    }
}
