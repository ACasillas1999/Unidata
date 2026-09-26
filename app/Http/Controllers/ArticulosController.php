<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

use App\Services\BranchConnectionManager;
use App\Services\PowerSalesService;
use App\Models\DbMasterArticle;

class ArticulosController extends Controller
{
    public function __construct(
        protected BranchConnectionManager $connectionManager,
        protected PowerSalesService $powerSales
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
            $normalize('Desc. P. Venta (Auto)') => 'des_precio_venta',
            $normalize('Desc. P. Venta (Calculado)') => 'des_precio_venta',
            $normalize('% Desc. V')       => 'des_precio_venta',
            $normalize('Desc_Precio_Venta') => 'des_precio_venta',
            $normalize('P. Especial')     => 'precio_especial',
            $normalize('P. Especial (Auto)') => 'precio_especial',
            $normalize('P. Especial (Calculado)') => 'precio_especial',
            $normalize('P. Espec.')       => 'precio_especial',
            $normalize('Precio_Especial') => 'precio_especial',
            $normalize('Precio Especial') => 'precio_especial',
            $normalize('Precio Especial (Auto)') => 'precio_especial',
            $normalize('Precio Especial (Calculado)') => 'precio_especial',
            $normalize('Desc. P. Espec')  => 'desc_precio_espec',
            $normalize('% Desc. E')       => 'desc_precio_espec',
            $normalize('Desc_Precio_Espec') => 'desc_precio_espec',
            $normalize('Precio 4')        => 'precio4',
            $normalize('Precio 4 (Auto)') => 'precio4',
            $normalize('Precio 4 (Calculado)') => 'precio4',
            $normalize('Precio4')         => 'precio4',
            $normalize('Precio4 (Auto)')  => 'precio4',
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
            $normalize('Area')            => 'area',
            $normalize('IVA')             => 'iva',
            $normalize('Ubicacion')       => 'ubicacion',
            $normalize('Sustituto')       => 'sustituto',
            $normalize('Sustituto1')      => 'sustituto1',
            $normalize('Sustituto 1')     => 'sustituto1',
            $normalize('Sustituto_1')     => 'sustituto1',
            $normalize('Sustituto2')      => 'sustituto2',
            $normalize('Sustituto 2')     => 'sustituto2',
            $normalize('Sustituto_2')     => 'sustituto2',
            $normalize('ID_Impuesto_SAT') => 'id_impuesto_sat',
            $normalize('Desc. Proveedor') => 'desc_proveedor',
            $normalize('Desc_Proveedor')  => 'desc_proveedor',
            $normalize('Desc Prov')       => 'desc_proveedor',
            $normalize('% Desc. Prov')    => 'desc_proveedor',
            $normalize('Precio Gerente')  => 'precio_gerente',
            $normalize('Precio Gerente (Auto)') => 'precio_gerente',
            $normalize('Precio Gerente (Calculado)') => 'precio_gerente',
            $normalize('Precio_Gerente')  => 'precio_gerente',
            $normalize('Resultado Desc Proveedor') => 'precio_gerente',
            $normalize('resultado_desc_proveedor') => 'precio_gerente',
            $normalize('Precio Tope')     => 'precio_tope',
            $normalize('Precio Tope (Auto)') => 'precio_tope',
            $normalize('Precio Tope (Calculado)') => 'precio_tope',
            $normalize('PrecioTope')      => 'precio_tope',
            $normalize('Precio_Tope')     => 'precio_tope',
            $normalize('Peso')            => 'peso',
            $normalize('Std Pack')        => 'std_pack',
            $normalize('StdPack')         => 'std_pack',
            $normalize('Std_Pack')        => 'std_pack',
            $normalize('Empaque')         => 'std_pack',
            $normalize('Crítico')         => 'critico',
            $normalize('Critico')         => 'critico',
            $normalize('Control Pedimentos') => 'control_pedimentos',
            $normalize('Control_Pedimentos') => 'control_pedimentos',
            $normalize('Pedimentos')      => 'control_pedimentos',
        ];

        $branchFieldMap = \App\Support\ArticuloFieldMap::map();

        return [$normalize, $standardMapping, $branchFieldMap];
    }

    public function index(Request $request): View
    {
        $search   = trim((string) $request->string('q'));
        $sucursal = $request->string('sucursal')->toString();
        $perPage  = (int) $request->input('per_page', 50);
        if (!in_array($perPage, [50, 100, 250, 500])) $perPage = 50;

        $sort = strtolower($request->input('sort', 'clave'));
        $dir  = strtolower($request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $sortMap = [
            'clave'            => 'Clave_Articulo',
            'descripcion'      => 'Descripcion',
            'unidad_medida'    => 'Unidad_Medida',
            'linea'            => 'Linea',
            'clasificacion'    => 'Clasificacion',
            'area'             => 'Area',
            'precio_lista'     => 'Precio_Lista',
            'precio_venta'     => 'Precio_Venta',
            'des_precio_venta' => 'Desc_Precio_Venta',
            'precio_especial'  => 'Precio_Especial',
            'precio4'          => 'Precio4',
            'costo_venta'      => 'CostoVenta',
            'costo_promedio'   => 'Costo_Promedio',
        ];

        $orderCol = $sortMap[$sort] ?? 'Clave_Articulo';

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
                );

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('Clave_Articulo', 'LIKE', "%{$search}%")
                      ->orWhere('Descripcion', 'LIKE', "%{$search}%");
                });
            }

            // Paginación directa en el motor SQL de la sucursal conectada
            $articles = $query->orderBy($orderCol, $dir)
                              ->paginate($perPage)
                              ->withQueryString();

        } catch (Throwable $e) {
            $error = 'Fallo de conexión en sucursal ' . ($branchesMap[$sucursal] ?? $sucursal) . ': ' . $e->getMessage();
        }

        return view('articulos.index', [
            'branches' => $branchesMap,
            'sucursal' => $sucursal,
            'search'   => $search,
            'sort'     => $sort,
            'dir'      => $dir,
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
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">ID_Impuesto_SAT</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Area</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">IVA</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Ubicacion</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Sustituto</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Fecha_Alta</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Habilitado</th>');
            fwrite($out, '</tr></thead><tbody>');

            $connection = $this->connectionManager->connect($sucursal);
            $query = $connection
                ->table('articulo')
                ->select(
                    'Clave_Articulo', 'Descripcion', 'Unidad_Medida', 'Linea', 'Clasificacion',
                    'MN_USD', 'Precio_Lista', 'Desc_Precio_Venta', 'Precio_Venta', 'Desc_Precio_Espec',
                    'Precio_Especial', 'Desc_Precio4', 'Precio4', 'Articulo_Kit', 'Margen_Minimo',
                    'Articulo_Serie', 'Color', 'Habilitado', 'Protocolo', 'IDSAT', 'CostoVenta', 
                    'PorcentajeDescuento', 'Area', 'IVA', 'Ubicacion', 'Sustituto', 'IDImpuestoSAT', 'Fecha_Alta'
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
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)($item->IDImpuestoSAT ?? '')) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)($item->Area ?? '')) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)($item->IVA ?? '')) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)($item->Ubicacion ?? '')) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)($item->Sustituto ?? '')) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)($item->Fecha_Alta ?? '')) . '</td>');
                    
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

    public function descargarMachote(Request $request)
    {
        $tipo = $request->input('tipo', 'con_datos'); // vacio | con_datos | catalogo

        $filename = match($tipo) {
            'vacio'    => 'machote_articulos_vacio.csv',
            'catalogo' => 'machote_articulos_catalogo_maestro.csv',
            default    => 'machote_articulos_con_datos.csv',
        };

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = [
            'Clave', 'Descripción', 'U.M.', 'Línea', 'Clasificación',
            'Area', 'IVA', 'Ubicacion', 'Sustituto', 'Sustituto 1', 'Sustituto 2', 'MN/USD',
            'P. Lista', 'P. Venta', 'Desc. P. Venta (Auto)', 'P. Especial (Auto)', 'Desc. P. Espec',
            'Precio 4 (Auto)', 'Desc. Precio 4', 'Desc. Proveedor', 'Precio Gerente (Auto)', '% Descuento', 'Precio Tope (Auto)', 'Costo Venta',
            'Art. Kit', 'Art. Serie', 'Mg Mín', 'Color', 'Protocolo',
            'IDSAT', 'ID_Impuesto_SAT', 'Peso', 'Std Pack', 'Crítico', 'Control Pedimentos', 'Estatus',
        ];

        $callback = function () use ($columns, $tipo) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM para apertura nativa en Excel sin problemas de acentos
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $columns);

            if ($tipo === 'vacio') {
                fclose($handle);
                return;
            }

            if ($tipo === 'catalogo') {
                // Exportar catálogo maestro formateado a 2 decimales en todos los precios
                $articles = DbMasterArticle::take(1000)->get();
                foreach ($articles as $art) {
                    $pLista = (float)($art->precio_lista ?? 0);
                    $dProv  = (float)($art->desc_proveedor ?? 0);
                    $pGerente = round($pLista * (100 - $dProv) / 100, 2);

                    $row = [
                        $art->clave,
                        $art->descripcion,
                        $art->unidad_medida,
                        $art->linea,
                        $art->clasificacion,
                        $art->area ?? 1,
                        number_format((float)($art->iva ?? 16), 2, '.', ''),
                        $art->ubicacion ?? 'GENERAL',
                        $art->sustituto ?? '0',
                        $art->sustituto1 ?? '0',
                        $art->sustituto2 ?? '0',
                        $art->mn_usd ?? 0,
                        number_format($pLista, 2, '.', ''),
                        number_format((float)($art->precio_venta ?? 0), 2, '.', ''),
                        number_format((float)($art->des_precio_venta ?? 0), 2, '.', ''),
                        number_format((float)($art->precio_especial ?? 0), 2, '.', ''),
                        number_format((float)($art->desc_precio_espec ?? 0), 2, '.', ''),
                        number_format((float)($art->precio4 ?? 0), 2, '.', ''),
                        number_format((float)($art->desc_precio4 ?? 0), 2, '.', ''),
                        number_format($dProv, 2, '.', ''),
                        number_format($pGerente, 2, '.', ''),
                        number_format((float)($art->porcetaje_descuento ?? 0), 2, '.', ''),
                        number_format((float)($art->precio_tope ?? 0), 2, '.', ''),
                        number_format((float)($art->costo_venta ?? 0), 2, '.', ''),
                        $art->articulo_kit ?? 0,
                        $art->articulo_serie ?? 0,
                        number_format((float)($art->margen_minimo ?? 0), 2, '.', ''),
                        $art->color ?? 0,
                        $art->protocolo ?? 0,
                        $art->idsat ?? '',
                        $art->id_impuesto_sat ?? '002',
                        number_format((float)($art->peso ?? 0), 2, '.', ''),
                        number_format((float)($art->std_pack ?? 1), 2, '.', ''),
                        $art->critico ?? 0,
                        $art->control_pedimentos ?? 0,
                        $art->habilitado ? 'ACTIVO' : 'INACTIVO',
                    ];
                    fputcsv($handle, $row);
                }
            } else {
                // con_datos (valores de ejemplo fijos a 2 decimales)
                $example1 = [
                    'ART001', 'EJEMPLO PRODUCTO XYZ', 'PZA', 'ELEC', 'ELECT',
                    '1', '16.00', 'GENERAL', '0', '0', '0', '0',
                    '100.00', '90.00', '10.00', '85.00', '15.00',
                    '80.00', '20.00', '10.00', '90.00', '30.00', '70.00', '0.00',
                    '0', '0', '20.00', '0', '0',
                    '43211501', '002', '1.00', '1.00', '0', '0', 'ACTIVO',
                ];
                $example2 = [
                    'ART002', 'EJEMPLO PRODUCTO ABC', 'PZA', 'HERR', 'HERRA',
                    '1', '16.00', 'GENERAL', '0', '0', '0', '0',
                    '200.00', '180.00', '10.00', '170.00', '15.00',
                    '160.00', '20.00', '12.00', '176.00', '25.00', '150.00', '0.00',
                    '0', '0', '20.00', '0', '0',
                    '43211501', '002', '2.00', '1.00', '0', '0', 'ACTIVO',
                ];
                fputcsv($handle, $example1);
                fputcsv($handle, $example2);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
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
                $normH = $normalize($h);
                if (isset($standardMapping[$normH])) {
                    $headerMap[$index] = $standardMapping[$normH];
                }
            }

            $updatedCount = 0;
            $branchesMap = $this->connectionManager->getActiveBranches()->pluck('name', 'code')->toArray();
            $branchStats = [];
            foreach ($branchesSelected as $suc) {
                if (isset($branchesMap[$suc])) {
                    $branchStats[$suc] = ['ok' => 0, 'fail' => 0, 'errors' => []];
                }
            }

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
                        // 1. Manejo de booleanos (excluyendo color que es un entero de 0 a 9)
                        if (in_array($field, ['habilitado', 'articulo_kit', 'articulo_serie', 'en_promocion', 'critico', 'control_pedimentos', 'mn_usd', 'protocolo'])) {
                            $val = (in_array(strtoupper((string)$val), ['ACTIVO', '1', 'SI', 'SÍ', 'S', 'TRUE', 'VERDADERO'])) ? 1 : 0;
                        }

                        if ($field === 'color') {
                            $val = is_numeric($val) ? (int)$val : 0;
                        }

                        // 2. Truncado según esquema real (evitar SQL Truncated errors)
                        if ($field === 'descripcion') $val = mb_substr((string)$val, 0, 200);
                        if ($field === 'linea') $val = mb_substr((string)$val, 0, 4);
                        if ($field === 'clasificacion') $val = mb_substr((string)$val, 0, 6);
                        if ($field === 'unidad_medida') $val = mb_substr((string)$val, 0, 4);
                        if ($field === 'ubicacion') $val = mb_substr((string)$val, 0, 10);
                        if ($field === 'idsat') $val = mb_substr((string)$val, 0, 25);
                        if ($field === 'id_impuesto_sat') $val = mb_substr((string)$val, 0, 3);
                        if (in_array($field, ['sustituto', 'sustituto1', 'sustituto2'])) $val = mb_substr((string)$val, 0, 40);

                        // 3. Gestión de numéricos (evitar Not Null errors)
                        $numericCols = [
                            'precio_lista', 'precio_venta', 'des_precio_venta', 'precio_especial', 
                            'desc_precio_espec', 'precio4', 'desc_precio4', 'desc_proveedor', 'precio_gerente', 'precio_tope', 'costo_venta', 
                            'porcetaje_descuento', 'margen_minimo', 'area', 'iva', 'peso',
                            'inventario_maximo', 'inventario_minimo', 'punto_reorden', 'std_pack'
                        ];
                        if (in_array($field, $numericCols) && (!is_numeric($val) || $val === '')) {
                            $val = 0;
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

                // --- GOBERNANZA: SOLO ACTUALIZACIONES ---
                $masterCurrent = DbMasterArticle::where('clave', $clave)->first();
                if (!$masterCurrent) continue; // Si no existe en el maestro, no se puede crear vía CSV

                // --- AUTO-CÁLCULO SIEMPRE DE CAMPOS DERIVADOS VÍA FÓRMULA (2 DECIMALES) ---
                $pLista = isset($updateDataMaster['precio_lista']) ? (float)$updateDataMaster['precio_lista'] : (float)($masterCurrent->precio_lista ?? 0);
                $d4     = isset($updateDataMaster['desc_precio4']) ? (float)$updateDataMaster['desc_precio4'] : (float)($masterCurrent->desc_precio4 ?? 0);
                $dEsp   = isset($updateDataMaster['desc_precio_espec']) ? (float)$updateDataMaster['desc_precio_espec'] : (float)($masterCurrent->desc_precio_espec ?? 0);
                $dProv  = isset($updateDataMaster['desc_proveedor']) ? (float)$updateDataMaster['desc_proveedor'] : (float)($masterCurrent->desc_proveedor ?? 0);
                $pDesc  = isset($updateDataMaster['porcetaje_descuento']) ? (float)$updateDataMaster['porcetaje_descuento'] : (float)($masterCurrent->porcetaje_descuento ?? 0);

                $calcP4 = round($pLista * (100 - $d4) / 100, 2);
                $updateDataMaster['precio4'] = $calcP4;
                $updateDataBranch['Precio4'] = $calcP4;

                $calcPEsp = round($pLista * (100 - $dEsp) / 100, 2);
                $updateDataMaster['precio_especial'] = $calcPEsp;
                $updateDataBranch['Precio_Especial'] = $calcPEsp;

                $calcPrecioGerente = round($pLista * (100 - $dProv) / 100, 2);
                $updateDataMaster['precio_gerente'] = $calcPrecioGerente;
                $updateDataBranch['Precio_gerente'] = $calcPrecioGerente;

                $calcPrecioTope = round($pLista * (100 - $pDesc) / 100, 2);
                $updateDataMaster['precio_tope'] = $calcPrecioTope;
                $updateDataBranch['PrecioTope']   = $calcPrecioTope;

                // --- AUDITORÍA ANTES DE ACTUALIZAR ---
                $auditEntries = [];
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
                            try {
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
                                $branchStats[$suc]['ok']++;
                            } catch (Throwable $e) {
                                $branchStats[$suc]['fail']++;
                                if (count($branchStats[$suc]['errors']) < 3) {
                                    $branchStats[$suc]['errors'][] = "{$clave}: " . $this->friendlyDbError($e);
                                }
                            }
                        }
                    }

                    // Sync a PowerSales (solo campos que cambiaron en esta fila; ver storage/logs/powersales.log)
                    $branchDataPs = array_merge(['Clave_Articulo' => $clave], $updateDataBranch);
                    $this->powerSales->syncArticulo($branchDataPs);
                    $this->powerSales->syncPriceListHeaders();
                    $this->powerSales->syncArticuloPriceListDetails($branchDataPs);
                    $this->powerSales->syncDiscountListHeaders();
                    $this->powerSales->syncArticuloDiscountListDetails($branchDataPs);
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

            $branchDetail = [];
            foreach ($branchStats as $suc => $stats) {
                $nombre = $branchesMap[$suc] ?? $suc;
                $line = "{$nombre}: {$stats['ok']} ok";
                if ($stats['fail'] > 0) {
                    $line .= ", {$stats['fail']} con error (" . implode('; ', $stats['errors']) . ($stats['fail'] > count($stats['errors']) ? '; ...' : '') . ')';
                }
                $branchDetail[] = $line;
            }

            return response()->json([
                'success' => true,
                'message' => "Proceso completado. Se procesaron {$updatedCount} artículos en el Maestro. Detalle por sucursal: " . implode(' | ', $branchDetail),
                'branch_stats' => $branchStats
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
                $normH = $normalize($h);
                if (isset($standardMapping[$normH])) {
                    $headerMap[$index] = $standardMapping[$normH];
                }
            }

            // Columnas del CSV no reconocidas por el sistema
            $unrecognized = [];
            foreach ($headerOriginal as $index => $h) {
                if (!isset($headerMap[$index])) {
                    $display = trim(preg_replace('/[\x00-\x1F]/', '', $h));
                    if ($display !== '') $unrecognized[] = $display;
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
                'desc_proveedor'      => 'Desc. Proveedor',
                'precio_gerente'      => 'Precio Gerente',
                'costo_venta'         => 'Costo Venta',
                'porcetaje_descuento' => '% Descuento',
                'articulo_kit'        => 'Art. Kit',
                'articulo_serie'      => 'Art. Serie',
                'margen_minimo'       => 'Mg Mín',
                'color'               => 'Color',
                'protocolo'           => 'Protocolo',
                'idsat'               => 'IDSAT',
                'habilitado'          => 'Estatus',
                'area'                => 'Area',
                'iva'                 => 'IVA',
                'ubicacion'           => 'Ubicacion',
                'sustituto'           => 'Sustituto',
                'id_impuesto_sat'     => 'ID Impuesto SAT',
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

                // --- REVISAR RECÁLCULO OBLIGATORIO DE CAMPOS VÍA FÓRMULA (2 DECIMALES) ---
                $pLista = isset($csvItem['precio_lista']) ? (float)$csvItem['precio_lista'] : (float)($masterItem->precio_lista ?? 0);
                $d4     = isset($csvItem['desc_precio4']) ? (float)$csvItem['desc_precio4'] : (float)($masterItem->desc_precio4 ?? 0);
                $dEsp   = isset($csvItem['desc_precio_espec']) ? (float)$csvItem['desc_precio_espec'] : (float)($masterItem->desc_precio_espec ?? 0);
                $dProv  = isset($csvItem['desc_proveedor']) ? (float)$csvItem['desc_proveedor'] : (float)($masterItem->desc_proveedor ?? 0);
                $pDesc  = isset($csvItem['porcetaje_descuento']) ? (float)$csvItem['porcetaje_descuento'] : (float)($masterItem->porcetaje_descuento ?? 0);

                $formulaFields = [
                    'precio4'         => ['label' => 'Precio 4',       'new' => round($pLista * (100 - $d4) / 100, 2),    'old' => round((float)($masterItem->precio4 ?? 0), 2)],
                    'precio_especial' => ['label' => 'P. Especial',     'new' => round($pLista * (100 - $dEsp) / 100, 2),  'old' => round((float)($masterItem->precio_especial ?? 0), 2)],
                    'precio_gerente'  => ['label' => 'Precio Gerente',  'new' => round($pLista * (100 - $dProv) / 100, 2), 'old' => round((float)($masterItem->precio_gerente ?? 0), 2)],
                    'precio_tope'     => ['label' => 'Precio Tope',     'new' => round($pLista * (100 - $pDesc) / 100, 2), 'old' => round((float)($masterItem->precio_tope ?? 0), 2)],
                ];

                foreach ($formulaFields as $fKey => $fInfo) {
                    // Solo evaluar fórmulas si su columna o sus detonadores están seleccionados en la UI
                    $isColSelected = in_array($fInfo['label'], $columnsSelected) || in_array('P. Lista', $columnsSelected);
                    if ($isColSelected && $fInfo['new'] !== $fInfo['old']) {
                        $hasDifference = true;
                        $rowDiff[$fKey] = [
                            'old'        => number_format($fInfo['old'], 2, '.', ''),
                            'new'        => number_format($fInfo['new'], 2, '.', ''),
                            'is_formula' => true
                        ];
                        $changedCols[$fInfo['label']] = true;
                    }
                }

                if ($hasDifference) {
                    $diffs[] = [
                        'clave'       => $clave,
                        'description' => $masterItem->descripcion,
                        'status'      => 'update',
                        'diff'        => $rowDiff,
                        'full_new'    => $csvItem,
                        'master_data' => $masterItem->toArray()
                    ];
                }
            }
            fclose($handle);

            // Construir mapa visible: header CSV → campo BD → label UI
            $columnMap = [];
            foreach ($headerMap as $index => $dbField) {
                $csvHeader = trim(preg_replace('/[\x00-\x1F]/', '', $headerOriginal[$index] ?? ''));
                if ($csvHeader === '') continue;
                $uiLabel = $labelMap[$dbField] ?? $dbField;
                $isSelected = false;
                foreach ($standardMapping as $uiNorm => $mapped) {
                    if ($mapped === $dbField && in_array($uiNorm, $colsUiNorm)) {
                        $isSelected = true;
                        break;
                    }
                }
                $columnMap[] = [
                    'csv'      => $csvHeader,
                    'field'    => $dbField,
                    'label'    => $uiLabel,
                    'selected' => $isSelected,
                ];
            }

            return response()->json([
                'success'      => true,
                'diffs'        => $diffs,
                'count'        => count($diffs),
                'changed_cols' => array_keys($changedCols),
                'column_map'   => $columnMap,
                'unrecognized' => $unrecognized,
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
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Error al procesar la descarga: ' . $e->getMessage());
        }
    }

    public function crear(): \Illuminate\View\View
    {
        $branches = $this->connectionManager->getActiveBranches();
        return view('articulos.crear', compact('branches'));
    }

    /**
     * Guarda un artículo manualmente en el Maestro y lo replica en todas las sucursales
     */
    public function storeManual(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'clave'               => 'required|string|max:40|unique:db_master.Articulos,clave',
            'descripcion'         => 'required|string|max:200',
            'unidad_medida'       => 'required|string|max:4',
            'linea'               => 'required|string|max:4',
            'clasificacion'       => 'required|string|max:6',
            'area'                => 'required|integer',
            'mn_usd'              => 'required|boolean',
            'precio_lista'        => 'nullable|numeric',
            'precio_venta'        => 'nullable|numeric',
            'des_precio_venta'    => 'nullable|numeric',
            'precio_especial'     => 'nullable|numeric',
            'desc_precio_espec'   => 'nullable|numeric',
            'precio4'             => 'nullable|numeric',
            'desc_precio4'        => 'nullable|numeric',
            'desc_proveedor'      => 'nullable|numeric',
            'precio_gerente'      => 'nullable|numeric',
            'precio_tope'         => 'nullable|numeric',
            'costo_venta'         => 'nullable|numeric',
            'porcetaje_descuento' => 'nullable|numeric',
            'articulo_kit'        => 'nullable|boolean',
            'articulo_serie'      => 'nullable|boolean',
            'margen_minimo'       => 'nullable|numeric',
            'color'               => 'nullable|integer|between:0,9',
            'color_branch'        => 'nullable|array',
            'color_branch.*'      => 'nullable|integer|between:0,9',
            'protocolo'           => 'nullable|boolean',
            'idsat'               => 'nullable|string|max:25',
            'id_impuesto_sat'     => 'nullable|string|max:3',
            'iva'                 => 'nullable|numeric',
            'precio_minimo'       => 'nullable|numeric',
            'desc_precio_minimo'  => 'nullable|numeric',
            'sustituto'           => 'nullable|string',
            'sustituto1'          => 'nullable|string',
            'sustituto2'          => 'nullable|string',
            'costo_promedio'      => 'nullable|numeric',
            'costo_promedio_ant'  => 'nullable|numeric',
            'costo_ult_compra'    => 'nullable|numeric',
            'fecha_ult_compra'    => 'nullable|date',
            'costo_compra_ant'    => 'nullable|numeric',
            'fecha_compra_ant'    => 'nullable|date',
            'en_promocion'        => 'nullable|boolean',
            'critico'             => 'nullable|boolean',
            'control_pedimentos'  => 'nullable|boolean',
            'ubicacion'           => 'nullable|string|max:10',
            'peso'                => 'nullable|numeric',
            'std_pack'            => 'nullable|numeric',
            'inventario_maximo'   => 'nullable|numeric',
            'inventario_minimo'   => 'nullable|numeric',
            'punto_reorden'       => 'nullable|numeric',
            'habilitado'          => 'required|boolean',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Automatización de campos fijos y valores por defecto para sucursales (evitar NOT NULL SQL errors)
            $data['fecha_alta']      = now()->toDateString();
            $data['id_impuesto_sat'] = !empty($data['id_impuesto_sat']) ? $data['id_impuesto_sat'] : '002';
            $data['iva']             = isset($data['iva']) && $data['iva'] !== '' && $data['iva'] !== null ? $data['iva'] : 16;
            $data['id_tipo_factor']  = 'Tasa';
            $data['area']            = isset($data['area']) && $data['area'] !== '' && $data['area'] !== null ? (int)$data['area'] : 1;
            $data['mn_usd']          = !empty($data['mn_usd']) ? 1 : 0;
            $data['color']           = isset($data['color']) && $data['color'] !== '' && $data['color'] !== null ? (int)$data['color'] : 0;
            $data['protocolo']       = !empty($data['protocolo']) ? 1 : 0;
            $data['articulo_kit']    = !empty($data['articulo_kit']) ? 1 : 0;
            $data['articulo_serie']  = !empty($data['articulo_serie']) ? 1 : 0;
            $data['en_promocion']    = !empty($data['en_promocion']) ? 1 : 0;
            $data['critico']         = !empty($data['critico']) ? 1 : 0;
            $data['control_pedimentos'] = !empty($data['control_pedimentos']) ? 1 : 0;
            $data['ubicacion']       = (isset($data['ubicacion']) && $data['ubicacion'] !== null && $data['ubicacion'] !== '') ? $data['ubicacion'] : 'GENERAL';
            $data['idsat']           = (isset($data['idsat']) && $data['idsat'] !== null && $data['idsat'] !== '') ? $data['idsat'] : '01010101';
            $data['sustituto']       = (isset($data['sustituto']) && $data['sustituto'] !== null && $data['sustituto'] !== '') ? $data['sustituto'] : '0';
            $data['sustituto1']      = (isset($data['sustituto1']) && $data['sustituto1'] !== null && $data['sustituto1'] !== '') ? $data['sustituto1'] : '0';
            $data['sustituto2']      = (isset($data['sustituto2']) && $data['sustituto2'] !== null && $data['sustituto2'] !== '') ? $data['sustituto2'] : '0';
            $data['peso']            = isset($data['peso']) && is_numeric($data['peso']) ? $data['peso'] : 0;
            $data['std_pack']        = isset($data['std_pack']) && is_numeric($data['std_pack']) ? $data['std_pack'] : 1;

            $numericFields = [
                'precio_lista', 'precio_venta', 'des_precio_venta', 'precio_especial',
                'desc_precio_espec', 'precio4', 'desc_precio4', 'desc_proveedor', 'precio_gerente',
                'precio_tope', 'costo_venta', 'porcetaje_descuento', 'margen_minimo'
            ];
            foreach ($numericFields as $nf) {
                if (!isset($data[$nf]) || $data[$nf] === null || $data[$nf] === '') {
                    $data[$nf] = 0;
                }
            }

            // SIEMPRE calcular precio_gerente con fórmula a 2 decimales
            $pLista = (float)($data['precio_lista'] ?? 0);
            $dProv  = (float)($data['desc_proveedor'] ?? 0);
            $data['precio_gerente'] = round($pLista * (100 - $dProv) / 100, 2);

            // Extraer color_branch del array $data antes de crear en db_master
            $colorBranchMap = $data['color_branch'] ?? [];
            unset($data['color_branch']);

            // 1. Crear en DB Master
            $master = \App\Models\DbMasterArticle::create($data);

            // 2. Logger Central
            $historialId = \Illuminate\Support\Facades\DB::table('csv_historial')->insertGetId([
                'archivo_nombre'      => 'CREACIÓN MANUAL',
                'archivo_path'        => null,
                'articulos_afectados' => 1,
                'sucursales_json'     => json_encode(['TODAS']),
                'fecha'               => now()
            ]);

            $branches = $this->connectionManager->getActiveBranches();
            $errors = [];

            // Logger dedicado para replicación
            $replLogger = Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/replicacion.log'),
            ]);
            $replLogger->info("--- INICIO REPLICACIÓN ARTÍCULO: " . $data['clave'] . " ---");
            $replLogger->info("Sucursales activas detectadas: " . $branches->count());
            
            // 3. Replicar a Sucursales (PascalCase / Original)
            [, , $branchFieldMap] = $this->getMappingResources();
            
            $branchData = [];
            foreach ($data as $field => $val) {
                if ($field === 'clave') {
                    $branchData['Clave_Articulo'] = $val;
                } elseif (isset($branchFieldMap[$field])) {
                    $branchData[$branchFieldMap[$field]] = $val;
                }
            }

            $successfulBranchCodes = [];
            foreach ($branches as $branch) {
                $replLogger->info("Sucursal {$branch->name} ({$branch->code}): Iniciando...");
                try {
                    $conn = $this->connectionManager->connect($branch->code);
                    $replLogger->info("Sucursal {$branch->name}: Conexión exitosa.");
                    
                    $localBranchData = $branchData;
                    
                    $branchCode = $branch->code;
                    $branchCodeUpper = strtoupper($branchCode);
                    $branchCodeLower = strtolower($branchCode);

                    $customColor = $colorBranchMap[$branchCode]
                        ?? $colorBranchMap[$branchCodeUpper]
                        ?? $colorBranchMap[$branchCodeLower]
                        ?? null;

                    if ($customColor !== '' && $customColor !== null) {
                        $localBranchData['Color'] = (int) $customColor;
                    }

                    $conn->table('articulo')->insert($localBranchData);
                    $successfulBranchCodes[] = $branch->code;
                    $replLogger->info("Sucursal {$branch->name}: Inserción exitosa.");
                } catch (\Throwable $e) {
                    $errorMsg = "Error en sucursal {$branch->name}: " . $e->getMessage();
                    $replLogger->error($errorMsg);
                    $errors[] = $errorMsg;
                }
            }
            $replLogger->info("--- FIN REPLICACIÓN ARTÍCULO: " . $data['clave'] . " ---");

            // 4. Sincronizar inmediatamente a la Matriz de Homologación
            try {
                $matrizData = [
                    'clave'               => $data['clave'],
                    'descripcion'         => $data['descripcion'],
                    'unidad_medida'       => $data['unidad_medida'],
                    'linea'               => $data['linea'],
                    'clasificacion'       => $data['clasificacion'],
                    'area'                => $data['area'] ?? 1,
                    'mn_usd'              => $data['mn_usd'] ?? 0,
                    'precio_lista'        => $data['precio_lista'] ?? 0,
                    'des_precio_venta'    => $data['des_precio_venta'] ?? 0,
                    'precio_venta'        => $data['precio_venta'] ?? 0,
                    'desc_precio_espec'   => $data['desc_precio_espec'] ?? 0,
                    'precio_especial'     => $data['precio_especial'] ?? 0,
                    'desc_precio4'        => $data['desc_precio4'] ?? 0,
                    'precio4'             => $data['precio4'] ?? 0,
                    'desc_proveedor'      => $data['desc_proveedor'] ?? 0,
                    'precio_gerente'      => $data['precio_gerente'] ?? 0,
                    'precio_tope'         => $data['precio_tope'] ?? 0,
                    'costo_venta'         => $data['costo_venta'] ?? 0,
                    'porcetaje_descuento' => $data['porcetaje_descuento'] ?? 0,
                    'articulo_kit'        => $data['articulo_kit'] ?? 0,
                    'articulo_serie'      => $data['articulo_serie'] ?? 0,
                    'margen_minimo'       => $data['margen_minimo'] ?? 0,
                    'color'               => $data['color'] ?? 0,
                    'protocolo'           => $data['protocolo'] ?? 0,
                    'idsat'               => $data['idsat'] ?? null,
                    'id_impuesto_sat'     => $data['id_impuesto_sat'] ?? '002',
                    'iva'                 => $data['iva'] ?? 16,
                    'habilitado'          => $data['habilitado'] ?? 1,
                    'fecha_alta'          => $data['fecha_alta'] ?? now()->toDateString(),
                ];

                $physicalCols = \App\Models\MatrizHomologacion::getPhysicalBranchColumns();
                foreach ($successfulBranchCodes as $bCode) {
                    $colName = \App\Models\MatrizHomologacion::resolveColumnName($bCode);
                    if (in_array($colName, $physicalCols)) {
                        $matrizData[$colName] = 1;
                    }
                }

                \App\Models\MatrizHomologacion::updateOrCreate(
                    ['clave' => $data['clave']],
                    $matrizData
                );
            } catch (\Throwable $e) {
                Log::warning("No se pudo actualizar matriz_homologacions al crear el artículo {$data['clave']}: " . $e->getMessage());
            }

            // Sync a PowerSales (no bloquea ni revierte si falla; ver storage/logs/powersales.log)
            $this->powerSales->syncArticulo($branchData);
            $this->powerSales->syncPriceListHeaders();
            $this->powerSales->syncArticuloPriceListDetails($branchData);
            $this->powerSales->syncDiscountListHeaders();
            $this->powerSales->syncArticuloDiscountListDetails($branchData);

            // Log de auditoría
            \Illuminate\Support\Facades\DB::table('csv_historial_detalles')->insert([
                'historial_id'   => $historialId,
                'clave'          => $data['clave'],
                'columna'        => 'TODAS (CREACIÓN)',
                'valor_anterior' => 'NUEVO',
                'valor_nuevo'    => $data['descripcion'],
                'sucursal'       => 'SISTEMA'
            ]);

            \Illuminate\Support\Facades\DB::commit();

            if (!empty($errors)) {
                return redirect()->route('db_master.index')->with('warning', 'Artículo creado en Maestro, pero falló la replicación en algunas sucursales: ' . implode(' | ', $errors));
            }

            return redirect()->route('db_master.index')->with('success', 'Artículo creado exitosamente y replicado en todas las sucursales.');

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error al crear el artículo: ' . $e->getMessage());
        }
    }

    /**
     * Convierte un error de BD (con SQL y datos completos) en un mensaje corto y legible.
     */
    private function friendlyDbError(Throwable $e): string
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
}
