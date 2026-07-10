<?php

namespace App\Http\Controllers;

use App\Services\BranchConnectionManager;
use App\Services\PowerSalesService;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;
use Throwable;

class InventarioController extends Controller
{
    public function __construct(
        protected BranchConnectionManager $connectionManager,
        protected PowerSalesService $powerSales
    ) {}

    /**
     * Columnas reales de la tabla `articuloalm` (misma tabla en todas las sucursales).
     * Se seleccionan explicitamente para no arrastrar columnas que puedan variar.
     */
    private function inventarioColumns(): array
    {
        return [
            'a.Clave_Articulo', 'a.Almacen', 'a.Inventario_Maximo', 'a.Inventario_Minimo',
            'a.Punto_Reorden', 'a.Rack', 'a.Existencia_Teorica', 'a.Existencia_Fisica',
            'a.Costo_Promedio', 'a.Apartado', 'a.PendienteDeEntrega', 'a.Capacidad',
        ];
    }

    public function index(Request $request): View
    {
        $search   = trim((string) $request->string('q'));
        $perPage  = (int) $request->input('per_page', 50);
        if (!in_array($perPage, [50, 100, 250, 500])) $perPage = 50;

        $branches    = $this->connectionManager->getActiveBranches();
        $branchesMap = $branches->pluck('name', 'code')->toArray();

        $sucursal = $request->string('sucursal')->toString();
        if (!$sucursal || !isset($branchesMap[$sucursal])) {
            $sucursal = $branches->first()?->code ?? '';
        }

        $error = null;
        $items = new Paginator([], $perPage);

        try {
            $conn  = $this->connectionManager->connect($sucursal);
            $query = $conn->table('articuloalm as a')
                ->leftJoin('almacenes as w', 'a.Almacen', '=', 'w.Almacen')
                ->leftJoin('articulo as art', 'a.Clave_Articulo', '=', 'art.Clave_Articulo')
                ->select(array_merge($this->inventarioColumns(), [
                    'art.Descripcion as descripcion',
                    'w.Nombre as almacen_nombre',
                ]));

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('a.Clave_Articulo', 'LIKE', "%{$search}%")
                      ->orWhere('art.Descripcion', 'LIKE', "%{$search}%");
                });
            }

            $items = $query->orderBy('a.Clave_Articulo')
                ->paginate($perPage)
                ->withQueryString();
        } catch (Throwable $e) {
            $error = 'Fallo de conexión en sucursal ' . ($branchesMap[$sucursal] ?? $sucursal) . ': ' . $e->getMessage();
        }

        return view('inventario.index', [
            'items'       => $items,
            'branchesMap' => $branchesMap,
            'sucursal'    => $sucursal,
            'search'      => $search,
            'error'       => $error,
            'per_page'    => $perPage,
        ]);
    }

    /**
     * Exporta la existencia (una sucursal o todas) a CSV con el mapeo PowerSales
     * de proteo_db.field_mapping (entity=articuloalm): ProductId, WarehouseId,
     * InventoryAvailable, etc.
     */
    public function export(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $branches    = $this->connectionManager->getActiveBranches();
        $branchesMap = $branches->pluck('name', 'code')->toArray();

        $sucursal = $request->string('sucursal')->toString();
        $todas    = ($sucursal === 'todas' || !isset($branchesMap[$sucursal]));
        $targets  = $todas ? array_keys($branchesMap) : [$sucursal];

        $cols    = $this->powerSales->mappingRows('articuloalm')->pluck('ps_field')->all();
        $allCols = $todas ? array_merge(['Sucursal'], $cols) : $cols;

        $filename = 'Inventario_PowerSales_' . ($todas ? 'TODAS' : $sucursal) . '_' . now()->format('Y-m-d_His') . '.csv';
        $responseHeaders = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = $this->inventarioColumns();

        $callback = function () use ($targets, $todas, $branchesMap, $allCols, $cols, $columns) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($out, $allCols);

            foreach ($targets as $branchCode) {
                try {
                    $conn = $this->connectionManager->connect($branchCode);
                } catch (Throwable $e) {
                    continue; // sucursal inaccesible, seguir con las demas
                }

                $conn->table('articuloalm as a')
                    ->select($columns)
                    ->orderBy('a.Clave_Articulo')
                    ->chunk(1000, function ($rows) use ($out, $cols, $todas, $branchCode, $branchesMap) {
                        foreach ($rows as $row) {
                            $source  = (array) $row;
                            $payload = $this->powerSales->buildInventarioPayload($source);

                            $line = [];
                            if ($todas) {
                                $line[] = $branchesMap[$branchCode] ?? $branchCode;
                            }
                            foreach ($cols as $col) {
                                $value = $payload[$col] ?? null;
                                // ProductId (SKU) puede ser solo digitos: forzar texto para
                                // que Excel no lo convierta a notacion cientifica.
                                if ($col === 'ProductId' && $value !== null && $value !== '' && is_numeric($value)) {
                                    $value = '="' . str_replace('"', '""', (string) $value) . '"';
                                }
                                $line[] = $value;
                            }
                            fputcsv($out, $line);
                        }
                    });
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }
}
