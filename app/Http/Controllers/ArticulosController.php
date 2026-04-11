<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

use App\Services\BranchConnectionManager;
use App\Models\DbMasterArticle;

class ArticulosController extends Controller
{
    public function __construct(
        protected BranchConnectionManager $connectionManager
    ) {}

    /**
     * Retorna los recursos necesarios para el mapeo de columnas (Normalización y diccionarios)
     */
    private function getMappingResources()
    {
        $normalize = function($str) {
            if (!$str) return '';
            $str = mb_strtolower((string)$str, 'UTF-8');
            $str = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $str);
            return preg_replace('/[^a-z0-9]/', '', $str);
        };

        $standardMapping = [
            $normalize('Clave')           => 'clave',
            $normalize('Clave_Articulo')  => 'clave',
            $normalize('Clave Articulo')  => 'clave',
            $normalize('Código')          => 'clave',
            $normalize('Codigo')          => 'clave',
            $normalize('Descripción')     => 'descripcion',
            $normalize('Descripcion')     => 'descripcion',
            $normalize('U.M.')            => 'unidad_medida',
            $normalize('Unidad_Medida')   => 'unidad_medida',
            $normalize('Unid. Med.')      => 'unidad_medida',
            $normalize('Unidad Medida')   => 'unidad_medida',
            $normalize('Línea')           => 'linea',
            $normalize('Linea')           => 'linea',
            $normalize('Clasificación')   => 'clasificacion',
            $normalize('Clasificacion')   => 'clasificacion',
            $normalize('MN/USD')          => 'mn_usd',
            $normalize('MN_USD')          => 'mn_usd',
            $normalize('M/U')             => 'mn_usd',
            $normalize('P. Lista')        => 'precio_lista',
            $normalize('Precio_Lista')    => 'precio_lista',
            $normalize('Precio Lista')    => 'precio_lista',
            $normalize('P. Venta')        => 'precio_venta',
            $normalize('Precio_Venta')    => 'precio_venta',
            $normalize('Precio Venta')    => 'precio_venta',
            $normalize('Desc. P. Venta')  => 'des_precio_venta',
            $normalize('% Desc. V')       => 'des_precio_venta',
            $normalize('Desc_Precio_Venta') => 'des_precio_venta',
            $normalize('P. Especial')     => 'precio_especial',
            $normalize('P. Espec.')       => 'precio_especial',
            $normalize('Precio_Especial') => 'precio_especial',
            $normalize('Precio Especial') => 'precio_especial',
            $normalize('Desc. P. Espec')  => 'desc_precio_espec',
            $normalize('% Desc. E')       => 'desc_precio_espec',
            $normalize('Desc_Precio_Espec') => 'desc_precio_espec',
            $normalize('Precio 4')        => 'precio4',
            $normalize('Precio4')         => 'precio4',
            $normalize('Desc. Precio 4')  => 'desc_precio4',
            $normalize('% Desc. 4')       => 'desc_precio4',
            $normalize('Desc_Precio4')    => 'desc_precio4',
            $normalize('Costo Venta')     => 'costo_venta',
            $normalize('CostoVenta')      => 'costo_venta',
            $normalize('Costo')           => 'costo_venta',
            $normalize('% Descuento')     => 'porcetaje_descuento',
            $normalize('PorcentajeDescuento') => 'porcetaje_descuento',
            $normalize('Porcentaje de Descuento') => 'porcetaje_descuento',
            $normalize('Art. Kit')        => 'articulo_kit',
            $normalize('Kit')             => 'articulo_kit',
            $normalize('Articulo_Kit')    => 'articulo_kit',
            $normalize('Art. Serie')      => 'articulo_serie',
            $normalize('Serie')           => 'articulo_serie',
            $normalize('Articulo_Serie')  => 'articulo_serie',
            $normalize('Mg Mín')          => 'margen_minimo',
            $normalize('Margen')          => 'margen_minimo',
            $normalize('Margen_Minimo')   => 'margen_minimo',
            $normalize('Color')           => 'color',
            $normalize('Protocolo')       => 'protocolo',
            $normalize('Prot.')           => 'protocolo',
            $normalize('IDSAT')           => 'idsat',
            $normalize('SAT')             => 'idsat',
            $normalize('Estatus')         => 'habilitado',
            $normalize('Estatus Global')  => 'habilitado',
            $normalize('Habilitado')      => 'habilitado',
        ];

        $branchFieldMap = [
            'clave'               => 'Clave_Articulo',
            'descripcion'         => 'Descripcion',
            'unidad_medida'       => 'Unidad_Medida',
            'linea'               => 'Linea',
            'clasificacion'       => 'Clasificacion',
            'mn_usd'              => 'MN_USD',
            'precio_lista'        => 'Precio_Lista',
            'precio_venta'        => 'Precio_Venta',
            'des_precio_venta'    => 'Desc_Precio_Venta',
            'precio_especial'     => 'Precio_Especial',
            'desc_precio_espec'   => 'Desc_Precio_Espec',
            'precio4'             => 'Precio4',
            'desc_precio4'        => 'Desc_Precio4',
            'costo_venta'         => 'CostoVenta',
            'porcetaje_descuento' => 'PorcentajeDescuento',
            'articulo_kit'        => 'Articulo_Kit',
            'articulo_serie'      => 'Articulo_Serie',
            'margen_minimo'       => 'Margen_Minimo',
            'color'               => 'Color',
            'protocolo'           => 'Protocolo',
            'idsat'               => 'IDSAT',
            'habilitado'          => 'Habilitado',
        ];

        return [$normalize, $standardMapping, $branchFieldMap];
    }

    public function index(Request $request): View
    {
        $search   = trim((string) $request->string('q'));
        $sucursal = $request->string('sucursal')->toString();
        $perPage  = (int) $request->input('per_page', 50);
        if (!in_array($perPage, [50, 100, 250, 500])) $perPage = 50;

        // Obtener lista de sucursales activas dinámicamente
        $branches = $this->connectionManager->getActiveBranches();
        $branchesMap = $branches->pluck('name', 'code')->toArray();

        // Validar que la sucursal seleccionada es válida, si no, usar la primera activa
        if (!$sucursal || !isset($branchesMap[$sucursal])) {
            $sucursal = $branches->first()?->code ?? 'deasa';
        }

        $error    = null;
        $articles = new Paginator([], $perPage);

        try {
            // Resolver conexión dinámica
            $connection = $this->connectionManager->connect($sucursal);

            $query = $connection
                ->table('articulo')
                ->select(
                    'Clave_Articulo', 'Descripcion', 'Unidad_Medida', 'Linea', 'Clasificacion',
                    'MN_USD', 'Precio_Lista', 'Desc_Precio_Venta', 'Precio_Venta', 'Desc_Precio_Espec',
                    'Precio_Especial', 'Desc_Precio4', 'Precio4', 'Articulo_Kit', 'Margen_Minimo',
                    'Articulo_Serie', 'Color', 'Habilitado', 'Protocolo', 'IDSAT', 'CostoVenta', 'PorcentajeDescuento'
                );

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('Clave_Articulo', 'LIKE', "%{$search}%")
                      ->orWhere('Descripcion', 'LIKE', "%{$search}%");
                });
            }

            // Paginación directa en el motor SQL de la sucursal conectada
            $articles = $query->orderBy('Clave_Articulo', 'asc')
                              ->paginate($perPage)
                              ->withQueryString();

        } catch (Throwable $e) {
            $error = 'Fallo de conexión en sucursal ' . ($branchesMap[$sucursal] ?? $sucursal) . ': ' . $e->getMessage();
        }

        return view('articulos.index', [
            'branches' => $branchesMap,
            'sucursal' => $sucursal,
            'search'   => $search,
            'articles' => $articles,
            'error'    => $error,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Exporta el catálogo de la sucursal seleccionada a XLS con tabla HTML
     */
    public function export(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        if (session()->isStarted()) {
            session()->save();
        }

        $search   = trim((string) $request->string('q'));
        $sucursal = $request->string('sucursal')->toString();

        // Obtener lista de sucursales activas dinámicamente
        $branches = $this->connectionManager->getActiveBranches();
        $branchesMap = $branches->pluck('name', 'code')->toArray();

        // Validar que la sucursal seleccionada es válida, si no, usar la primera activa
        if (!$sucursal || !isset($branchesMap[$sucursal])) {
            $sucursal = $branches->first()?->code ?? 'deasa';
        }

        $branchName = $branchesMap[$sucursal];
        $filename   = 'Articulos_' . $branchName . '_' . now()->format('Y-m-d_His') . '.xls';

        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($sucursal, $branchName, $search) {
            $out = fopen('php://output', 'w');

            // HTML Header para Excel
            fwrite($out, '<html xmlns:x="urn:schemas-microsoft-com:office:excel">');
            fwrite($out, '<head><meta charset="utf-8"></head><body>');
            fwrite($out, '<table border="1" style="font-family: Arial, sans-serif; font-size: 11px;">');
            
            // Header Row
            fwrite($out, '<thead><tr>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Clave_Articulo</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Descripcion</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Unidad_Medida</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Linea</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Clasificacion</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">MN_USD</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Precio_Lista</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Precio_Venta</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Desc_Precio_Venta</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Precio_Especial</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Desc_Precio_Espec</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Precio4</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Desc_Precio4</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">CostoVenta</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">PorcentajeDescuento</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Articulo_Kit</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Articulo_Serie</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Margen_Minimo</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Color</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Protocolo</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">IDSAT</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Habilitado</th>');
            fwrite($out, '</tr></thead><tbody>');

            $connection = $this->connectionManager->connect($sucursal);
            $query = $connection
                ->table('articulo')
                ->select(
                    'Clave_Articulo', 'Descripcion', 'Unidad_Medida', 'Linea', 'Clasificacion',
                    'MN_USD', 'Precio_Lista', 'Desc_Precio_Venta', 'Precio_Venta', 'Desc_Precio_Espec',
                    'Precio_Especial', 'Desc_Precio4', 'Precio4', 'Articulo_Kit', 'Margen_Minimo',
                    'Articulo_Serie', 'Color', 'Habilitado', 'Protocolo', 'IDSAT', 'CostoVenta', 'PorcentajeDescuento'
                );

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('Clave_Articulo', 'LIKE', "%{$search}%")
                      ->orWhere('Descripcion', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy('Clave_Articulo', 'asc');

            $query->chunkById(500, function ($rows) use ($out) {
                foreach ($rows as $item) {
                    fwrite($out, '<tr>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Clave_Articulo) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Descripcion) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Unidad_Medida) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Linea) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Clasificacion) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->MN_USD) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Precio_Lista) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Precio_Venta) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Desc_Precio_Venta) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Precio_Especial) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Desc_Precio_Espec) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Precio4) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Desc_Precio4) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->CostoVenta) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->PorcentajeDescuento) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Articulo_Kit) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Articulo_Serie) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Margen_Minimo) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Color) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Protocolo) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->IDSAT) . '</td>');
                    
                    if ($item->Habilitado) {
                        fwrite($out, '<td style="background-color:#d1fae5; color:#065f46; text-align:center; font-weight:bold;">ACTIVO</td>');
                    } else {
                        fwrite($out, '<td style="background-color:#fef3c7; color:#92400e; text-align:center;">INACTIVO</td>');
                    }
                    fwrite($out, '</tr>');
                }
            }, 'Clave_Articulo');

            fwrite($out, '</tbody></table></body></html>');
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function subirForm(): View
    {
        $branchesMap = $this->connectionManager->getActiveBranches()->pluck('name', 'code')->toArray();

        return view('articulos.subir', [
            'branches' => $branchesMap,
        ]);
    }

    public function procesarSubida(Request $request)
    {
        $request->validate([
            'csv_file'  => 'required|file|mimes:csv,txt',
            'branches'  => 'required|array',
            'columns'   => 'required|array',
        ]);

        $branchesSelected = $request->input('branches');
        $columnsSelected  = $request->input('columns');
        $file = $request->file('csv_file');

        [$normalize, $standardMapping, $branchFieldMap] = $this->getMappingResources();

        try {
            // Guardar el archivo físicamente para futuras descargas
            $storedFile = $file->store('uploads/csv_history');
            $fullPath = Storage::disk('local')->path($storedFile);

            $handle = fopen($fullPath, "r");
            if (!$handle) throw new \Exception("No se pudo abrir el archivo guardado.");

            $rawHeaderLine = fgets($handle);
            if (!$rawHeaderLine) throw new \Exception("Archivo vacío.");

            $delimiter = (str_contains($rawHeaderLine, ';')) ? ';' : ',';
            rewind($handle);
            $headerOriginal = fgetcsv($handle, 0, $delimiter);

            // Mapear qué índice del CSV corresponde a qué campo de BD
            $headerMap = [];
            foreach ($headerOriginal as $index => $h) {
                // Quitar BOM y caracteres raros
                $h = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h);
                $normH = $normalize($h);
                
                if (isset($standardMapping[$normH])) {
                    $headerMap[$index] = $standardMapping[$normH];
                }
            }

            $updatedCount = 0;
            $branchesMap = $this->connectionManager->getActiveBranches()->pluck('name', 'code')->toArray();

            // ── INICIAR REGISTRO DE HISTORIAL ──
            $historialId = DB::table('csv_historial')->insertGetId([
                'archivo_nombre'      => $file->getClientOriginalName(),
                'archivo_path'        => $storedFile, // Ruta relativa para descarga
                'articulos_afectados' => 0,
                'sucursales_json'     => json_encode($branchesSelected),
                'fecha'               => now()
            ]);

            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $item = [];
                foreach ($row as $index => $value) {
                    if (isset($headerMap[$index])) {
                        $item[$headerMap[$index]] = trim($value);
                    }
                }

                $clave = $item['clave'] ?? null;
                if (!$clave) continue;

                $updateDataMaster = [];
                $updateDataBranch = [];

                // Normalizamos las columnas seleccionadas en la UI
                $colsUiNorm = array_map($normalize, $columnsSelected);

                foreach ($item as $field => $val) {
                    if ($field === 'clave') continue;

                    // Encontrar si este field corresponde a una columna seleccionada en la UI
                    $foundInUi = false;
                    foreach ($standardMapping as $uiNameNorm => $mappedField) {
                        if ($mappedField === $field && in_array($uiNameNorm, $colsUiNorm)) {
                            $foundInUi = true;
                            break;
                        }
                    }

                    if ($foundInUi) {
                        if ($field === 'habilitado') {
                            $val = (in_array(strtoupper($val), ['ACTIVO', '1', 'SI', 'SÍ', 'S'])) ? 1 : 0;
                        }
                        
                        // Para el Maestro (snake_case estándar)
                        $updateDataMaster[$field] = $val;

                        // Para las Sucursales (PascalCase / Original)
                        if (isset($branchFieldMap[$field])) {
                            $updateDataBranch[$branchFieldMap[$field]] = $val;
                        }
                    }
                }

                if (empty($updateDataMaster)) continue;

                // --- AUDITORÍA ANTES DE ACTUALIZAR ---
                $auditEntries = [];

                // 1. Audit Master
                $masterCurrent = DbMasterArticle::where('clave', $clave)->first();
                if ($masterCurrent) {
                    foreach ($updateDataMaster as $field => $newVal) {
                        $oldVal = $masterCurrent->{$field};
                        if ((string)$oldVal !== (string)$newVal) {
                            $auditEntries[] = [
                                'historial_id' => $historialId,
                                'clave' => $clave,
                                'columna' => $field,
                                'valor_anterior' => (string)$oldVal,
                                'valor_nuevo' => (string)$newVal,
                                'sucursal' => 'maestro'
                            ];
                        }
                    }
                    // Actualizar Master
                    $masterCurrent->update($updateDataMaster);
                }

                // 2. Audit & Update Sucursales
                if (!empty($updateDataBranch)) {
                    foreach ($branchesSelected as $suc) {
                        if (isset($branchesMap[$suc])) {
                            $connection = $this->connectionManager->connect($suc);
                            
                            $sucCurrent = $connection->table('articulo')->where('Clave_Articulo', $clave)->first();
                            if ($sucCurrent) {
                                foreach ($updateDataBranch as $fieldPascal => $newVal) {
                                    $oldVal = $sucCurrent->{$fieldPascal} ?? null;
                                    if ((string)$oldVal !== (string)$newVal) {
                                        $auditEntries[] = [
                                            'historial_id' => $historialId,
                                            'clave' => $clave,
                                            'columna' => $fieldPascal,
                                            'valor_anterior' => (string)$oldVal,
                                            'valor_nuevo' => (string)$newVal,
                                            'sucursal' => $suc
                                        ];
                                    }
                                }
                                // Actualizar Sucursal
                                $connection->table('articulo')
                                    ->where('Clave_Articulo', $clave)
                                    ->update($updateDataBranch);
                            }
                        }
                    }
                }

                // Guardar auditoría si hubo cambios
                if (!empty($auditEntries)) {
                    DB::table('csv_historial_detalles')->insert($auditEntries);
                }

                $updatedCount++;
            }
            fclose($handle);

            // Actualizar el conteo final en el historial
            DB::table('csv_historial')
                ->where('id', $historialId)
                ->update(['articulos_afectados' => $updatedCount]);

            return response()->json([
                'success' => true,
                'message' => "Proceso completado. Se procesaron {$updatedCount} artículos en el Maestro y " . count($branchesSelected) . " sucursales."
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Previsualiza los cambios del CSV comparándolos con el DB Master
     */
    public function previewSubida(Request $request)
    {
        try {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt',
                'columns'  => 'required|array',
            ]);

            $file = $request->file('csv_file');
            $columnsSelected = $request->input('columns');

            [$normalize, $standardMapping, $branchFieldMap] = $this->getMappingResources();

            $handle = fopen($file->getRealPath(), "r");
            $rawHeaderLine = fgets($handle);
            $delimiter = (str_contains($rawHeaderLine, ';')) ? ';' : ',';
            rewind($handle);
            $headerOriginal = fgetcsv($handle, 0, $delimiter);

            $headerMap = [];
            foreach ($headerOriginal as $index => $h) {
                $h = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h);
                $normH = $normalize($h);
                
                if (isset($standardMapping[$normH])) {
                    $headerMap[$index] = $standardMapping[$normH];
                }
            }

            $diffs = [];
            $colsUiNorm = array_map($normalize, $columnsSelected);
            $count = 0;
            $changedCols = [];

            // Mapeo inverso de field_name -> Label UI ORIGINAL (utilizado para el auto-select)
            $labelMap = [
                'descripcion'         => 'Descripción',
                'unidad_medida'       => 'U.M.',
                'linea'               => 'Línea',
                'clasificacion'       => 'Clasificación',
                'mn_usd'              => 'MN/USD',
                'precio_lista'        => 'P. Lista',
                'precio_venta'        => 'P. Venta',
                'des_precio_venta'    => 'Desc. P. Venta',
                'precio_especial'     => 'P. Especial',
                'desc_precio_espec'   => 'Desc. P. Espec',
                'precio4'             => 'Precio 4',
                'desc_precio4'        => 'Desc. Precio 4',
                'costo_venta'         => 'Costo Venta',
                'porcetaje_descuento' => '% Descuento',
                'articulo_kit'        => 'Art. Kit',
                'articulo_serie'      => 'Art. Serie',
                'margen_minimo'       => 'Mg Mín',
                'color'               => 'Color',
                'protocolo'           => 'Protocolo',
                'idsat'               => 'IDSAT',
                'habilitado'          => 'Estatus',
            ];

            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                if ($count > 500) break; // Límite aumentado para previsualización
                $count++;

                $csvItem = [];
                foreach ($row as $index => $value) {
                    if (isset($headerMap[$index])) {
                        $csvItem[$headerMap[$index]] = trim($value);
                    }
                }

                $clave = $csvItem['clave'] ?? null;
                if (!$clave) continue;

                $masterItem = DbMasterArticle::where('clave', $clave)->first();
                if (!$masterItem) {
                    // Nuevo artículo (no está en el maestro)
                    $diffs[] = [
                        'clave'  => $clave,
                        'status' => 'new',
                        'data'   => $csvItem
                    ];
                    continue;
                }

                $rowDiff = [];
                $hasDifference = false;

                foreach ($csvItem as $field => $csvVal) {
                    if ($field === 'clave') continue;

                    // Verificar si el campo fue seleccionado en la UI
                    $foundInUi = false;
                    foreach ($standardMapping as $uiNameNorm => $mappedField) {
                        if ($mappedField === $field && in_array($uiNameNorm, $colsUiNorm)) {
                            $foundInUi = true;
                            break;
                        }
                    }
                    if (!$foundInUi) continue;

                    if ($field === 'habilitado') {
                        $csvVal = (in_array(strtoupper($csvVal), ['ACTIVO', '1', 'SI', 'SÍ', 'S'])) ? 1 : 0;
                    }

                    $masterVal = $masterItem->{$field};
                    
                    // Comparación flexible (números vs strings, etc)
                    $isDifferent = false;
                    if (is_numeric($csvVal) && is_numeric($masterVal)) {
                        $isDifferent = round((float)$csvVal, 4) !== round((float)$masterVal, 4);
                    } else {
                        $isDifferent = (string)$csvVal !== (string)$masterVal;
                    }

                    if ($isDifferent) {
                        $hasDifference = true;
                        $rowDiff[$field] = [
                            'old' => $masterVal,
                            'new' => $csvVal
                        ];
                        // Registrar cuál columna del UI tiene cambios (para el botón inteligente)
                        if (isset($labelMap[$field])) {
                            $changedCols[$labelMap[$field]] = true;
                        }
                    }
                }

                if ($hasDifference) {
                    $diffs[] = [
                        'clave'       => $clave,
                        'description' => $masterItem->descripcion,
                        'status'      => 'update',
                        'diff'        => $rowDiff,
                        'full_new'    => $csvItem
                    ];
                }
            }
            fclose($handle);

            return response()->json([
                'success'      => true,
                'diffs'        => $diffs,
                'count'        => count($diffs),
                'changed_cols' => array_keys($changedCols)
            ]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    /**
     * Muestra el historial de subidas de CSV
     */
    public function historialSubidas()
    {
        try {
            $historial = DB::table('csv_historial')
                ->orderBy('fecha', 'desc')
                ->paginate(20);

            return view('articulos.historial', compact('historial'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Error al cargar el historial: ' . $e->getMessage());
        }
    }

    /**
     * Revierte los cambios de una subida específica
     */
    public function revertirSubida($id)
    {
        try {
            $historial = DB::table('csv_historial')->where('id', $id)->first();
            
            if (!$historial) {
                return response()->json(['success' => false, 'message' => 'Registro de historial no encontrado.'], 404);
            }

            if ($historial->revertido) {
                return response()->json(['success' => false, 'message' => 'Esta subida ya ha sido revertida anteriormente.'], 400);
            }

            // Obtener todos los cambios de esta subida
            $detalles = DB::table('csv_historial_detalles')
                ->where('historial_id', $id)
                ->get();

            // Revertir cada cambio
            foreach ($detalles as $log) {
                if ($log->sucursal === 'maestro') {
                    // Restaurar en Maestro
                    DbMasterArticle::where('clave', $log->clave)->update([
                        $log->columna => $log->valor_anterior
                    ]);
                } else {
                    // Restaurar en Sucursal
                    try {
                        $connection = $this->connectionManager->connect($log->sucursal);
                        $connection->table('articulo')
                            ->where('Clave_Articulo', $log->clave)
                            ->update([
                                $log->columna => $log->valor_anterior
                            ]);
                    } catch (Throwable $e) {
                        // Si falla una sucursal, intentamos seguir con las demás
                        continue;
                    }
                }
            }

            // Marcar como revertido
            DB::table('csv_historial')->where('id', $id)->update(['revertido' => 1]);

            return response()->json([
                'success' => true,
                'message' => 'Subida revertida con éxito. Los valores originales han sido restaurados.'
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al revertir la subida: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retorna los detalles de auditoría de una subida específica
     */
    public function historialDetalles($id)
    {
        try {
            // Intentar en la base de datos nueva (unidata)
            $detalles = DB::table('csv_historial_detalles')
                ->where('historial_id', $id)
                ->get();
            
            // Si está vacío, intentar en la vieja (db_master) por compatibilidad
            if ($detalles->isEmpty()) {
                $detalles = DB::connection('db_master')
                    ->table('csv_historial_detalles')
                    ->where('historial_id', $id)
                    ->get();
            }
            
            return response()->json($detalles);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Descarga el archivo CSV original de una subida
     */
    public function descargarCsv($id)
    {
        try {
            $hist = DB::table('csv_historial')->where('id', $id)->first();
            
            if (!$hist || !$hist->archivo_path) {
                return redirect()->back()->with('error', 'El archivo físico no está disponible para esta subida.');
            }

            $path = Storage::disk('local')->path($hist->archivo_path);

            if (!Storage::disk('local')->exists($hist->archivo_path)) {
                return redirect()->back()->with('error', 'El archivo no se encuentra en el servidor.');
            }

            return response()->download($path, $hist->archivo_nombre);
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Error al procesar la descarga: ' . $e->getMessage());
        }
    }
}
