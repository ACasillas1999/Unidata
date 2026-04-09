<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

use App\Services\BranchConnectionManager;

class ArticulosController extends Controller
{
    public function __construct(
        protected BranchConnectionManager $connectionManager
    ) {}

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

        // Función interna para normalizar cadenas (quitar acentos, a minúsculas, etc)
        $normalize = function($str) {
            $str = mb_strtolower($str, 'UTF-8');
            $str = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $str);
            return preg_replace('/[^a-z0-9]/', '', $str);
        };

        // Mapeo normalizado para búsqueda flexible
        $normMapping = [
            $normalize('Clave')          => 'Clave_Articulo',
            $normalize('Código')         => 'Clave_Articulo',
            $normalize('Codigo')         => 'Clave_Articulo',
            $normalize('Descripción')    => 'Descripcion',
            $normalize('U.M.')           => 'Unidad_Medida',
            $normalize('Unidad Medida')  => 'Unidad_Medida',
            $normalize('Línea')          => 'Linea',
            $normalize('Clasificación')  => 'Clasificacion',
            $normalize('MN/USD')         => 'MN_USD',
            $normalize('P. Lista')       => 'Precio_Lista',
            $normalize('Precio Lista')   => 'Precio_Lista',
            $normalize('P. Venta')       => 'Precio_Venta',
            $normalize('Precio Venta')   => 'Precio_Venta',
            $normalize('Desc. P. Venta') => 'Desc_Precio_Venta',
            $normalize('P. Especial')    => 'Precio_Especial',
            $normalize('Precio Especial') => 'Precio_Especial',
            $normalize('Desc. P. Espec') => 'Desc_Precio_Espec',
            $normalize('Precio 4')       => 'Precio4',
            $normalize('Desc. Precio 4') => 'Desc_Precio4',
            $normalize('Costo Venta')    => 'CostoVenta',
            $normalize('% Descuento')    => 'PorcentajeDescuento',
            $normalize('Art. Kit')       => 'Articulo_Kit',
            $normalize('Art. Serie')     => 'Articulo_Serie',
            $normalize('Mg Mín')         => 'Margen_Minimo',
            $normalize('Color')          => 'Color',
            $normalize('Protocolo')      => 'Protocolo',
            $normalize('IDSAT')          => 'IDSAT',
            $normalize('Estatus')        => 'Habilitado',
        ];

        try {
            $handle = fopen($file->getRealPath(), "r");
            if (!$handle) throw new \Exception("No se pudo abrir el archivo.");

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
                
                // Búsqueda especial para la clave: si el header normalizado CONTIENE 'codigo' o 'clave'
                if (str_contains($normH, 'clave') || str_contains($normH, 'codigo')) {
                    $headerMap[$index] = 'Clave_Articulo';
                    continue;
                }

                if (isset($normMapping[$normH])) {
                    $headerMap[$index] = $normMapping[$normH];
                }
            }

            $updatedCount = 0;
            $branchesMap = $this->connectionManager->getActiveBranches()->pluck('name', 'code')->toArray();

            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $item = [];
                foreach ($row as $index => $value) {
                    if (isset($headerMap[$index])) {
                        $item[$headerMap[$index]] = trim($value);
                    }
                }

                $clave = $item['Clave_Articulo'] ?? null;
                if (!$clave) continue;

                $updateData = [];
                // Solo actualizar los campos que el usuario seleccionó en la UI
                // Primero normalizamos las columnas seleccionadas en la UI para compararlas
                $colsUiNorm = array_map($normalize, $columnsSelected);

                foreach ($item as $dbField => $val) {
                    if ($dbField === 'Clave_Articulo') continue;

                    // Encontrar si este dbField corresponde a una columna seleccionada en la UI
                    $foundInUi = false;
                    foreach ($normMapping as $uiNameNorm => $mappedDbField) {
                        if ($mappedDbField === $dbField && in_array($uiNameNorm, $colsUiNorm)) {
                            $foundInUi = true;
                            break;
                        }
                    }

                    if ($foundInUi) {
                        if ($dbField === 'Habilitado') {
                            $val = (in_array(strtoupper($val), ['ACTIVO', '1', 'SI', 'SÍ', 'S'])) ? 1 : 0;
                        }
                        $updateData[$dbField] = $val;
                    }
                }

                if (empty($updateData)) continue;

                foreach ($branchesSelected as $suc) {
                    if (isset($branchesMap[$suc])) {
                        $connection = $this->connectionManager->connect($suc);
                        $connection
                            ->table('articulo')
                            ->where('Clave_Articulo', $clave)
                            ->update($updateData);
                    }
                }
                $updatedCount++;
            }
            fclose($handle);

            return response()->json([
                'success' => true,
                'message' => "Proceso completado. Se procesaron {$updatedCount} artículos en " . count($branchesSelected) . " sucursales."
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }
}
