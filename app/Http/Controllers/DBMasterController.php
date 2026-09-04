<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Console\Commands\SyncDbMaster;
use App\Models\MatrizHomologacion;
use App\Models\DbMasterArticle;
use App\Models\DbMasterSyncHistory;
use App\Services\BranchConnectionManager;
use App\Services\PowerSalesService;
use App\Support\ArticuloFieldMap;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DBMasterController extends Controller
{
    public function __construct(
        protected PowerSalesService $powerSales,
        protected BranchConnectionManager $connectionManager
    ) {}

    /**
     * Obtiene el listado de sucursales activas y sus columnas correspondientes.
     */
    private function getDynamicBranches(): array
    {
        $activeBranches = \App\Models\Branch::query()->active()->get();
        $physicalCols = MatrizHomologacion::getPhysicalBranchColumns();
        
        $branches = [];
        foreach ($activeBranches as $branch) {
            $colName = MatrizHomologacion::resolveColumnName($branch->code);
            if (in_array($colName, $physicalCols)) {
                $branches[strtoupper($branch->name)] = ['col' => $colName];
            }
        }
        return $branches;
    }

    public function index(Request $request): View
    {
        $search  = trim((string) $request->string('q'));
        $perPage = (int) $request->input('per_page', 50);
        if (!in_array($perPage, [50, 100, 250, 500])) {
            $perPage = 50;
        }

        $sort = strtolower($request->input('sort', 'clave'));
        $dir  = strtolower($request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $sortMap = [
            'clave'            => 'clave',
            'descripcion'      => 'descripcion',
            'unidad_medida'    => 'unidad_medida',
            'linea'            => 'linea',
            'clasificacion'    => 'clasificacion',
            'area'             => 'area',
            'precio_lista'     => 'precio_lista',
            'precio_venta'     => 'precio_venta',
            'des_precio_venta' => 'des_precio_venta',
            'precio_especial'  => 'precio_especial',
            'precio4'          => 'precio4',
            'costo_venta'      => 'costo_venta',
            'costo_promedio'   => 'costo_promedio',
        ];

        $orderCol = $sortMap[$sort] ?? 'clave';

        $error    = null;
        $articles = new LengthAwarePaginator([], 0, $perPage);
        $stats    = ['universo' => 0, 'last_sync' => 'Nunca'];
        $branches = [];

        try {
            $branches = $this->getDynamicBranches();
            $query = DbMasterArticle::query();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('clave', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy($orderCol, $dir);
            $paginator = $query->paginate($perPage)->withQueryString();

            $paginator->getCollection()->transform(function ($item) use ($branches) {
                $out = (object) $item->toArray();
                $out->Codigo_Deasa      = $item->clave;
                $out->Descripcion_Deasa = $item->descripcion;
                foreach ($branches as $info) {
                    $out->{$info['col']} = 'ACTIVO';
                }
                return $out;
            });

            $articles = $paginator;
            $stats['universo'] = $paginator->total();

            $lastSync = DbMasterSyncHistory::orderBy('created_at', 'DESC')->first();
            $stats['last_sync'] = $lastSync ? $lastSync->created_at->format('d/m/Y H:i') : 'Nunca';

        } catch (\Throwable $e) {
            file_put_contents(storage_path('logs/sync_dbmaster_error.log'), $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            $error = 'Error consultando la base de datos maestra: ' . $e->getMessage();
        }

        return view('db_master.index', [
            'articles' => $articles,
            'error'    => $error,
            'search'   => $search,
            'sort'     => $sort,
            'dir'      => $dir,
            'stats'    => $stats,
            'per_page' => $perPage,
            'branches' => $branches,
        ]);
    }

    /**
     * Actualiza un articulo del Maestro (edicion manual desde el modal), replica el
     * cambio a todas las sucursales activas y sincroniza a PowerSales.
     */
    public function updateManual(Request $request, $id)
    {
        $article = DbMasterArticle::find($id);
        if (!$article) {
            return response()->json(['success' => false, 'message' => 'Artículo no encontrado.'], 404);
        }

        $data = $request->validate([
            'descripcion'         => 'nullable|string|max:200',
            'linea'               => 'sometimes|required|string|max:4',
            'clasificacion'       => 'sometimes|required|string|max:6',
            'area'                => 'sometimes|required|integer',
            'unidad_medida'       => 'sometimes|required|string|max:4',
            'color'               => 'nullable|boolean',
            'protocolo'           => 'nullable|boolean',
            'articulo_kit'        => 'nullable|boolean',
            'articulo_serie'      => 'nullable|boolean',
            'habilitado'          => 'nullable|boolean',
            'mn_usd'              => 'nullable|boolean',
            'precio_lista'        => 'nullable|numeric',
            'precio_venta'        => 'nullable|numeric',
            'des_precio_venta'    => 'nullable|numeric',
            'precio_especial'     => 'nullable|numeric',
            'desc_precio_espec'   => 'nullable|numeric',
            'precio4'             => 'nullable|numeric',
            'desc_precio4'        => 'nullable|numeric',
            'precio_minimo'       => 'nullable|numeric',
            'desc_precio_minimo'  => 'nullable|numeric',
            'precio_tope'         => 'nullable|numeric',
            'margen_minimo'       => 'nullable|numeric',
            'costo_venta'         => 'nullable|numeric',
            'costo_promedio'      => 'nullable|numeric',
            'costo_promedio_ant'  => 'nullable|numeric',
            'inventario_maximo'   => 'nullable|numeric',
            'inventario_minimo'   => 'nullable|numeric',
            'punto_reorden'       => 'nullable|numeric',
            'ubicacion'           => 'nullable|string|max:10',
            'peso'                => 'nullable|numeric',
            'std_pack'            => 'nullable|numeric',
            'costo_ult_compra'    => 'nullable|numeric',
            'fecha_ult_compra'    => 'nullable|date',
            'costo_compra_ant'    => 'nullable|numeric',
            'idsat'               => 'nullable|string|max:25',
            'id_impuesto_sat'     => 'nullable|string|max:3',
            'iva'                 => 'nullable|numeric',
            'sustituto'           => 'nullable|string',
            'sustituto1'          => 'nullable|string',
            'sustituto2'          => 'nullable|string',
            'en_promocion'        => 'nullable|boolean',
            'critico'             => 'nullable|boolean',
            'control_pedimentos'  => 'nullable|boolean',
        ]);

        try {
            $article->update($data);

            // Replicar a sucursales activas (misma logica que ArticulosController::procesarSubida)
            $branchData = ArticuloFieldMap::toBranchFormat(array_merge(['clave' => $article->clave], $data));
            $branches   = $this->connectionManager->getActiveBranches();
            $branchResults = [];

            foreach ($branches as $branch) {
                try {
                    $conn = $this->connectionManager->connect($branch->code);
                    $conn->table('articulo')->where('Clave_Articulo', $article->clave)->update($branchData);
                    $branchResults[] = "✓ {$branch->name}";
                } catch (\Throwable $e) {
                    $branchResults[] = "✗ {$branch->name}: " . $this->friendlyDbError($e);
                }
            }

            // Sync a PowerSales con el articulo ya actualizado completo (no solo los campos editados)
            $fullBranchData = ArticuloFieldMap::toBranchFormat($article->fresh()->toArray());
            $this->powerSales->syncArticulo($fullBranchData);
            $this->powerSales->syncPriceListHeaders();
            $this->powerSales->syncArticuloPriceListDetails($fullBranchData);

            $message = 'Artículo actualizado en Maestro. Detalle de sucursales: ' . implode(' | ', $branchResults);

            return response()->json(['success' => true, 'message' => $message, 'branch_results' => $branchResults]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Convierte un error de BD (con SQL y datos completos) en un mensaje corto y legible.
     */
    private function friendlyDbError(\Throwable $e): string
    {
        $msg = $e->getMessage();

        if (str_contains($msg, 'command denied')) {
            return 'sin permisos de escritura (usuario de solo lectura)';
        }
        if (preg_match("/Column '([^']+)' cannot be null/i", $msg, $m)) {
            return "el campo '{$m[1]}' no admite nulos en esta sucursal";
        }

        $pos = strpos($msg, '(Connection:');
        return $pos !== false ? trim(substr($msg, 0, $pos)) : $msg;
    }

    /**
     * Inicia la sincronización en background (no bloquea el request)
     */
    public function sync()
    {
        $statusFile = SyncDbMaster::statusFile();

        // Si ya hay una corriendo, no duplicar
        if (file_exists($statusFile)) {
            $data = json_decode(file_get_contents($statusFile), true);
            if (isset($data['status']) && $data['status'] === 'running') {
                return response()->json(['status' => 'already_running', 'message' => 'Ya hay una sincronización en progreso.']);
            }
        }

        // Estado inicial
        file_put_contents($statusFile, json_encode([
            'status'     => 'running',
            'message'    => 'Iniciando sincronización...',
            'step'       => 0,
            'total'      => 0,
            'updated_at' => time(),
        ], JSON_UNESCAPED_UNICODE));

        // Lanzar proceso de fondo (no muere si cierras la pestaña)
        $php     = PHP_BINARY;
        $artisan = base_path('artisan');
        $log     = storage_path('logs') . DIRECTORY_SEPARATOR . 'sync_dbmaster_bg.log';
        $cmd     = 'start "" /B "' . $php . '" "' . $artisan . '" unidata:sync-dbmaster >> "' . $log . '" 2>&1';
        pclose(popen($cmd, 'r'));

        return response()->json(['status' => 'started']);
    }

    /**
     * Polling: retorna JSON con el estado actual del proceso de fondo
     */
    public function syncStatus()
    {
        $statusFile = SyncDbMaster::statusFile();
        if (!file_exists($statusFile)) {
            return response()->json(['status' => 'idle', 'message' => 'Sin sincronización reciente.', 'step' => 0, 'total' => 0]);
        }

        return response()->json(
            json_decode(file_get_contents($statusFile), true) ?? []
        );
    }

    /**
     * Retorna el historial de sincronizaciones
     */
    public function history()
    {
        try {
            $history = DbMasterSyncHistory::orderBy('created_at', 'DESC')->limit(50)->get();
            return response()->json($history);
        } catch (\Throwable $e) {
            file_put_contents(storage_path('logs/sync_dbmaster_error.log'), $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exporta la base maestra a XLS
     */
    public function export(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $filename = 'DB_Master_Articulos_' . now()->format('Y-m-d_His') . '.xls';
        $headers  = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            echo "<table>";
            echo "<tr><th>Clave</th><th>Descripción</th></tr>";
            DbMasterArticle::query()->chunk(500, function ($rows) {
                foreach ($rows as $article) {
                    echo "<tr><td>{$article->clave}</td><td>{$article->descripcion}</td></tr>";
                }
            });
            echo "</table>";
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Exporta el catalogo maestro con el mapeo de campos PowerSales aplicado, en CSV.
     * Una sola tabla: columnas de Articulos + Listas de Precios juntas (mismo SKU por fila),
     * usando los nombres de campo PowerSales (SKU, Name, PL_Precio_Lista, etc.) como encabezado.
     *
     * CSV no tiene "tipo de celda" como XLS, asi que para evitar que Excel convierta
     * codigos largos (SKU, ClaveSat, etc.) a notacion cientifica o les recorte ceros a la
     * izquierda, esos valores se envuelven en ="valor" (truco estandar: Excel lo evalua
     * como formula de texto en vez de auto-detectar numero).
     */
    public function exportPowerSales()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $groups = $this->powerSales->mappingGroups();
        $productCols   = $groups['articulo']['rows']->pluck('ps_field')->all();
        $pricelistCols = $groups['pricelist']['rows']->pluck('ps_field')->all();
        $allCols       = array_merge($productCols, $pricelistCols);

        // Solo estos campos son montos/cantidades reales. Todo lo demas (SKU, ProductCode,
        // ClaveSat, CategoryId, etc.) se fuerza a texto aunque sean solo digitos.
        $numericFields = [
            'Cost', 'UnitsPerBox', 'CasePerPallet', 'ConversionFactor', 'LoyaltyPct',
            'PL_Precio_Lista', 'PL_Precio_Venta', 'PL_Precio_Especial', 'PL_Precio4',
        ];

        $filename = 'DB_Master_PowerSales_' . now()->format('Y-m-d_His') . '.csv';
        $responseHeaders = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $formatCell = function ($value, string $col) use ($numericFields) {
            if ($value === null || $value === '') {
                return '';
            }
            $isForcedNumeric = in_array($col, $numericFields, true) && is_numeric($value);
            if (!$isForcedNumeric && is_numeric($value)) {
                // Codigo que parece numero (SKU, ClaveSat, etc.): forzar texto en Excel.
                return '="' . str_replace('"', '""', (string) $value) . '"';
            }
            return $value;
        };

        $callback = function () use ($allCols, $productCols, $pricelistCols, $formatCell) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel

            fputcsv($out, $allCols);

            DbMasterArticle::query()->orderBy('clave')->chunk(500, function ($rows) use ($out, $allCols, $productCols, $pricelistCols, $formatCell) {
                foreach ($rows as $article) {
                    $branchData = ArticuloFieldMap::toBranchFormat($article->toArray());
                    $payload    = array_merge(
                        $this->powerSales->buildArticuloPayload($branchData),
                        $this->powerSales->buildPriceListPayload($branchData)
                    );

                    $line = [];
                    foreach ($allCols as $col) {
                        $line[] = $formatCell($payload[$col] ?? null, $col);
                    }
                    fputcsv($out, $line);
                }
            });

            fclose($out);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }
}
