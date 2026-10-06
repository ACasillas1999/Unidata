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

        $sort  = strtolower($request->input('sort', 'clave'));
        $dir   = strtolower($request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $sortMap = [
            'clave'             => 'a.Clave_Articulo',
            'descripcion'       => 'art.Descripcion',
            'almacen'           => 'a.Almacen',
            'existencia_fisica' => 'a.Existencia_Fisica',
            'existencia_teorica'=> 'a.Existencia_Teorica',
            'apartado'          => 'a.Apartado',
            'pendiente_entrega' => 'a.PendienteDeEntrega',
            'minimo'            => 'a.Inventario_Minimo',
            'maximo'            => 'a.Inventario_Maximo',
            'reorden'           => 'a.Punto_Reorden',
        ];

        $orderCol = $sortMap[$sort] ?? 'a.Clave_Articulo';

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

            $items = $query->orderBy($orderCol, $dir)
                ->paginate($perPage)
                ->withQueryString();
        } catch (Throwable $e) {
            $error = 'Fallo de conexión en sucursal ' . ($branchesMap[$sucursal] ?? $sucursal) . ': ' . $e->getMessage();
        }

        return view('inventario.index', [
            'items'       => $items,
            'branchesMap' => $branchesMap,
            'branches'    => $branches,
            'sucursal'    => $sucursal,
            'search'      => $search,
            'sort'        => $sort,
            'dir'         => $dir,
            'error'       => $error,
            'per_page'    => $perPage,
        ]);
    }

    /**
     * Descarga plantilla CSV de ejemplo para carga masiva de minimos y maximos.
     */
    public function descargarPlantillaCsv()
    {
        $filename = 'Plantilla_Minimos_Maximos.csv';
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($out, ['clave_articulo', 'inventario_maximo', 'inventario_minimo']);
            fputcsv($out, ['EJEMPLO001', '100', '10']);
            fputcsv($out, ['EJEMPLO002', '50', '5']);
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Resuelve las sucursales destino según la selección del usuario.
     */
    private function resolveTargetBranches(Request $request): array
    {
        $allBranches = $this->connectionManager->getActiveBranches();
        $targetType  = $request->input('target_type', 'todas');

        if ($targetType === 'especifica') {
            $code = $request->input('branch_code');
            return $allBranches->filter(fn($b) => $b->code === $code)->all();
        }

        if ($targetType === 'seleccionadas') {
            $codes = (array) $request->input('branch_codes', []);
            return $allBranches->filter(fn($b) => in_array($b->code, $codes, true))->all();
        }

        return $allBranches->all();
    }

    /**
     * Actualiza manualmente Inventario_Minimo y Inventario_Maximo de un solo artículo.
     */
    public function updateItem(Request $request)
    {
        $request->validate([
            'clave_articulo'    => 'required|string',
            'inventario_minimo' => 'required|numeric|min:0',
            'inventario_maximo' => 'required|numeric|min:0',
        ]);

        $clave = trim($request->input('clave_articulo'));
        $min   = (float) $request->input('inventario_minimo');
        $max   = (float) $request->input('inventario_maximo');

        $targets = $this->resolveTargetBranches($request);
        if (empty($targets)) {
            return redirect()->back()->with('error', 'Debes seleccionar al menos una sucursal destino.');
        }

        $resultados = [];
        foreach ($targets as $branch) {
            try {
                $conn = $this->connectionManager->connect($branch->code);
                
                $affected = $conn->table('articuloalm')
                    ->where('Clave_Articulo', $clave)
                    ->update([
                        'Inventario_Minimo' => $min,
                        'Inventario_Maximo' => $max,
                    ]);

                // Si no existía en articuloalm pero sí en la tabla articulo, insertar en Almacen 1
                if ($affected === 0) {
                    $articuloExiste = $conn->table('articulo')->where('Clave_Articulo', $clave)->exists();
                    if ($articuloExiste) {
                        $conn->table('articuloalm')->insert([
                            'Clave_Articulo'     => $clave,
                            'Almacen'            => 1,
                            'Inventario_Minimo'  => $min,
                            'Inventario_Maximo'  => $max,
                            'Punto_Reorden'      => 0,
                            'Rack'               => '',
                            'Existencia_Teorica'  => 0,
                            'Existencia_Fisica'   => 0,
                            'Costo_Promedio'     => 0,
                            'Apartado'           => 0,
                            'PendientedeEntrega' => 0,
                            'Capacidad'          => 0,
                        ]);
                        $affected = 1;
                    }
                }

                $resultados[] = [
                    'sucursal' => $branch->name,
                    'status'   => $affected > 0 ? 'ok' : 'not_found',
                    'message'  => $affected > 0 ? "Actualizados Mín: {$min} | Máx: {$max}" : 'El artículo no existe en esta sucursal.',
                ];
            } catch (Throwable $e) {
                $resultados[] = [
                    'sucursal' => $branch->name,
                    'status'   => 'error',
                    'message'  => $e->getMessage(),
                ];
            }
        }

        $exitosos = count(array_filter($resultados, fn($r) => $r['status'] === 'ok'));
        $total    = count($resultados);

        return redirect()->back()
            ->with('success', "Mínimo/Máximo de [{$clave}] actualizado en {$exitosos}/{$total} sucursales.")
            ->with('minmax_modal', [
                'clave'      => $clave,
                'min'        => $min,
                'max'        => $max,
                'exitosos'   => $exitosos,
                'total'      => $total,
                'resultados' => $resultados,
            ]);
    }

    /**
     * Mapea encabezados de CSV dinámicamente para soportar diferentes formatos.
     */
    private function parseCsvHeaders(array $headers): array
    {
        $map = ['clave' => null, 'max' => null, 'min' => null];

        foreach ($headers as $index => $header) {
            $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $header)));

            if (in_array($clean, ['clavearticulo', 'clave', 'sku', 'codigo', 'productid'], true)) {
                $map['clave'] = $index;
            } elseif (in_array($clean, ['inventariomaximo', 'inventariomax', 'maximo', 'max'], true)) {
                $map['max'] = $index;
            } elseif (in_array($clean, ['inventariominimo', 'inventariomin', 'minimo', 'min'], true)) {
                $map['min'] = $index;
            }
        }

        if ($map['clave'] === null) $map['clave'] = 0;
        if ($map['max'] === null)   $map['max']   = 1;
        if ($map['min'] === null)   $map['min']   = 2;

        return $map;
    }

    /**
     * Actualiza masivamente Inventario_Minimo y Inventario_Maximo desde un archivo CSV.
     */
    public function updateMasivo(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $targets = $this->resolveTargetBranches($request);
        if (empty($targets)) {
            return redirect()->back()->with('error', 'Debes seleccionar al menos una sucursal destino.');
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return redirect()->back()->with('error', 'No se pudo abrir el archivo CSV.');
        }

        // Remover BOM de UTF-8 si existe
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return redirect()->back()->with('error', 'El archivo CSV está vacío.');
        }
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);

        // Detectar delimitador (coma o punto y coma)
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        // Parsear encabezados
        $headers = str_getcsv($firstLine, $delimiter);
        $colMap  = $this->parseCsvHeaders($headers);

        $rows = [];
        while (($data = fgetcsv($handle, 4096, $delimiter)) !== false) {
            if (empty($data) || (count($data) == 1 && trim($data[0]) === '')) {
                continue;
            }

            $clave = trim((string)($data[$colMap['clave']] ?? ''));
            $max   = trim((string)($data[$colMap['max']] ?? ''));
            $min   = trim((string)($data[$colMap['min']] ?? ''));

            if ($clave === '' || strtolower($clave) === 'clave_articulo') continue;

            $rows[] = [
                'clave' => $clave,
                'max'   => is_numeric($max) ? (float)$max : 0,
                'min'   => is_numeric($min) ? (float)$min : 0,
            ];
        }
        fclose($handle);

        if (empty($rows)) {
            return redirect()->back()->with('error', 'No se encontraron filas válidas en el archivo CSV.');
        }

        // Agrupar filas por clave única para procesar la última ocurrencia si hay repetidos
        $filasUnicas = [];
        foreach ($rows as $r) {
            $filasUnicas[$r['clave']] = $r;
        }

        $totalFilas = count($filasUnicas);
        $resultados = [];

        foreach ($targets as $branch) {
            $actualizados  = 0;
            $noEncontrados = 0;
            $errores       = 0;

            try {
                $conn = $this->connectionManager->connect($branch->code);

                foreach ($filasUnicas as $r) {
                    try {
                        $affected = $conn->table('articuloalm')
                            ->where('Clave_Articulo', $r['clave'])
                            ->update([
                                'Inventario_Minimo' => $r['min'],
                                'Inventario_Maximo' => $r['max'],
                            ]);

                        if ($affected > 0) {
                            $actualizados++;
                        } else {
                            $articuloExiste = $conn->table('articulo')->where('Clave_Articulo', $r['clave'])->exists();
                            if ($articuloExiste) {
                                $conn->table('articuloalm')->insert([
                                    'Clave_Articulo'     => $r['clave'],
                                    'Almacen'            => 1,
                                    'Inventario_Minimo'  => $r['min'],
                                    'Inventario_Maximo'  => $r['max'],
                                    'Punto_Reorden'      => 0,
                                    'Rack'               => '',
                                    'Existencia_Teorica'  => 0,
                                    'Existencia_Fisica'   => 0,
                                    'Costo_Promedio'     => 0,
                                    'Apartado'           => 0,
                                    'PendientedeEntrega' => 0,
                                    'Capacidad'          => 0,
                                ]);
                                $actualizados++;
                            } else {
                                $noEncontrados++;
                            }
                        }
                    } catch (Throwable $e) {
                        $errores++;
                    }
                }

                $resultados[] = [
                    'sucursal'      => $branch->name,
                    'status'        => 'ok',
                    'actualizados'  => $actualizados,
                    'noEncontrados' => $noEncontrados,
                    'errores'       => $errores,
                ];
            } catch (Throwable $e) {
                $resultados[] = [
                    'sucursal' => $branch->name,
                    'status'   => 'error',
                    'message'  => $e->getMessage(),
                ];
            }
        }

        $exitosos = count(array_filter($resultados, fn($r) => $r['status'] === 'ok'));
        $totalSuc  = count($resultados);

        return redirect()->back()
            ->with('success', "Procesadas {$totalFilas} filas CSV en {$exitosos}/{$totalSuc} sucursales.")
            ->with('csv_modal', [
                'totalFilas' => $totalFilas,
                'exitosos'   => $exitosos,
                'totalSuc'   => $totalSuc,
                'resultados' => $resultados,
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
