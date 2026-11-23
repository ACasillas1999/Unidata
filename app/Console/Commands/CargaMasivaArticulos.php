<?php

namespace App\Console\Commands;

use App\Http\Controllers\ArticulosController;
use Illuminate\Console\Command;
use Throwable;

/**
 * Carga masiva de artículos en segundo plano.
 * La lanza ArticulosController::procesarSubida() con `start /B` (mismo patrón que
 * unidata:sync-matriz y unidata:sync-dbmaster). Corre sin el límite de tiempo del
 * servidor web y reporta su avance en storage/app/carga_masiva_status.json.
 */
class CargaMasivaArticulos extends Command
{
    protected $signature   = 'unidata:carga-masiva {historial : ID de csv_historial creado por procesarSubida}';
    protected $description = 'Procesa en segundo plano una carga masiva de artículos (Actualizar Artículos).';

    public function handle(): int
    {
        set_time_limit(0);
        $historialId = (int) $this->argument('historial');

        /** @var ArticulosController $ctrl */
        $ctrl = app(ArticulosController::class);

        $base = $ctrl->leerEstadoCarga() ?? [];
        if ((int) ($base['historial_id'] ?? 0) !== $historialId) {
            $base = ['historial_id' => $historialId, 'started_at' => time()];
        }
        $total = (int) ($base['total'] ?? 0);

        $ultimoGuardado = 0;
        $avance = function (int $filasLeidas, int $procesados) use ($ctrl, &$base, $total, &$ultimoGuardado) {
            // Escribir como máximo una vez por segundo (las filas omitidas pasan muy rápido)
            if (time() === $ultimoGuardado) return;
            $ultimoGuardado = time();

            $actual = $ctrl->leerEstadoCarga() ?? $base;
            $base = array_merge($actual, [
                'status'     => 'running',
                'step'       => $filasLeidas,
                'total'      => $total,
                'procesados' => $procesados,
                'message'    => !empty($actual['cancel_requested'])
                    ? 'Cancelando... se detendrá al terminar el artículo en curso.'
                    : "Procesando fila {$filasLeidas} de {$total} ({$procesados} artículos actualizados)",
            ]);
            $ctrl->escribirEstadoCarga($base);
        };

        $cancelado = function () use ($ctrl): bool {
            return !empty(($ctrl->leerEstadoCarga() ?? [])['cancel_requested']);
        };

        try {
            $resultado = $ctrl->ejecutarCargaMasiva($historialId, $avance, $cancelado);
        } catch (Throwable $e) {
            $resultado = [
                'success'    => false,
                'procesados' => 0,
                'message'    => 'Error inesperado en la carga: ' . $e->getMessage(),
            ];
        }

        $final = array_merge($ctrl->leerEstadoCarga() ?? $base, [
            'status'       => !empty($resultado['cancelado']) ? 'cancelled' : (($resultado['success'] ?? false) ? 'done' : 'error'),
            'historial_id' => $historialId,
            'step'         => $total,
            'total'        => $total,
            'procesados'   => (int) ($resultado['procesados'] ?? 0),
            'message'      => $resultado['message'] ?? '',
            'resultado'    => $resultado,
            'finished_at'  => time(),
        ]);
        if (!empty($resultado['cancelado'])) {
            // Al cancelar, el avance real es donde se detuvo, no el total
            $final['step'] = (int) (($ctrl->leerEstadoCarga() ?? [])['step'] ?? 0);
        }
        unset($final['cancel_requested']);
        $ctrl->escribirEstadoCarga($final);

        $this->info("Carga masiva #{$historialId}: {$final['status']} — {$final['message']}");
        return self::SUCCESS;
    }
}
