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

        // Resetear columnas de todas las sucursales activas
        $colsToReset = $branches->map(fn ($b) => 'en_' . strtolower($b->code))->toArray();
        // Solo resetear columnas que existen en el modelo
        $validCols = array_intersect($colsToReset, MatrizHomologacion::make()->getFillable());

        if (!empty($validCols)) {
            MatrizHomologacion::query()->update(array_fill_keys($validCols, false));
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
            $colName = 'en_' . strtolower($branch->code);

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

                $connection
                    ->table('articulo')
                    ->select('Clave_Articulo', 'Descripcion', 'Habilitado')
                    ->orderBy('Clave_Articulo')
                    ->chunk(2000, function ($articles) use ($colName, &$totalProcessed) {
                        $upsertData = [];
                        foreach ($articles as $art) {
                            $upsertData[] = [
                                'clave'       => $art->Clave_Articulo,
                                'descripcion' => $art->Descripcion ?: 'SIN DESCRIPCIÓN',
                                $colName      => $art->Habilitado ? 1 : 0,
                            ];
                        }
                        MatrizHomologacion::upsert($upsertData, ['clave'], [$colName]);
                        $totalProcessed += count($articles);
                    });

                $this->info("   [OK] {$branch->name}: {$totalProcessed} registros.");

            } catch (\Throwable $e) {
                $this->error("   [ERROR] {$branch->name}: " . $e->getMessage());
            }
        }

        $this->writeStatus('done', '¡Sincronización completada exitosamente!', $total, $total);
        $this->info('¡Sincronización Finalizada con Éxito!');
    }
}
