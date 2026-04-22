<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\MatrizHomologacion;
use App\Models\DbMasterArticle;
use App\Models\DbMasterSyncHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncDbMaster extends Command
{
    protected $signature   = 'unidata:sync-dbmaster';
    protected $description = 'Sincroniza la Matriz Maestra hacia DB Master de manera progresiva.';

    public static function statusFile(): string
    {
        return storage_path('app/sync_dbmaster_status.json');
    }

    private function writeStatus(string $status, string $message, int $step = 0, int $total = 0): void
    {
        $json = json_encode([
            'status'     => $status,
            'message'    => $message,
            'step'       => $step,
            'total'      => $total,
            'updated_at' => time(),
        ], JSON_UNESCAPED_UNICODE);
        file_put_contents(self::statusFile(), $json, LOCK_EX);
    }

    public function handle()
    {
        try {
            // 0. Calculate Branches
            $activeBranches = Branch::query()->active()->get();
            $physicalCols = MatrizHomologacion::getPhysicalBranchColumns();
            
            $branches = [];
            foreach ($activeBranches as $branch) {
                $colName = MatrizHomologacion::resolveColumnName($branch->code);
                if (in_array($colName, $physicalCols)) {
                    $branches[strtoupper($branch->name)] = ['col' => $colName];
                }
            }

            // Calculate total elements to process
            $query = MatrizHomologacion::query();
            foreach ($branches as $branch) {
                $query->where($branch['col'], 1);
            }
            $totalExpected = $query->count();
            
            if ($totalExpected === 0) {
                $this->writeStatus('done', 'Sin articulos que cumplan cobertura 100%.', 0, 0);
                return;
            }

            $this->writeStatus('running', 'Limpiando tabla destino...', 0, $totalExpected);

            // 1. Limpiar la tabla de destino
            DbMasterArticle::truncate();

            // 2. Insertar chunks
            $totalInserted = 0;
            
            $query->select([
                'clave', 'descripcion', 'unidad_medida', 'linea', 'clasificacion', 'area',
                'mn_usd', 'precio_lista', 'des_precio_venta', 'precio_venta',
                'desc_precio_espec', 'precio_especial', 'desc_precio4', 'precio4',
                'desc_precio_minimo', 'precio_minimo', 'precio_tope', 'desc_proveedor',
                'articulo_kit', 'margen_minimo', 'articulo_serie', 'color',
                'protocolo', 'idsat', 'costo_venta', 'porcetaje_descuento',
                'clave_proveedor_1', 'costo_act_prov_1', 'clave_prov_2', 'costo_act_prov_2', 'clave_prov_3', 'costo_act_prov_3', 'fecha_costo_act_p',
                'inventario_maximo', 'inventario_minimo', 'punto_reorden', 'existencia_teorica', 'existencia_fisica',
                'costo_promedio', 'costo_promedio_ant', 'costo_ult_compra', 'fecha_ult_compra', 'costo_compra_ant', 'fecha_compra_ant', 'fecha_alta',
                'en_promocion', 'critico', 'control_pedimentos', 'id_impuesto_sat', 'iva', 'id_tipo_factor',
                'sustituto', 'sustituto1', 'sustituto2', 'articulo_conversion', 'conversion', 'peso', 'ubicacion', 'std_pack',
                'habilitado'
            ])->chunk(1000, function($sourceArticles) use (&$totalInserted, $totalExpected) {
                $now = now();
                $chunk = $sourceArticles->toArray();
                foreach($chunk as &$c) {
                    $c['created_at'] = $now;
                    $c['updated_at'] = $now;
                }
                DbMasterArticle::insert($chunk);
                $totalInserted += count($chunk);
                
                $this->writeStatus('running', "Insertando articulos... ($totalInserted / $totalExpected)", $totalInserted, $totalExpected);
            });

            // 3. Registrar historial
            DbMasterSyncHistory::create([
                'total_articulos' => $totalInserted
            ]);

            $this->writeStatus('done', 'Sincronizacion maestra completada exitosamente.', $totalExpected, $totalExpected);

        } catch (\Throwable $e) {
            $this->writeStatus('error', 'Error crítico: ' . $e->getMessage(), 0, 100);
            file_put_contents(storage_path('logs/sync_dbmaster_error.log'), $e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
    }
}
