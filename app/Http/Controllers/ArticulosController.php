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
     * Sanitiza recursivamente cualquier estructura de datos para asegurar codificación UTF-8 válida,
     * convirtiendo mensajes o cadenas con codificaciones comunes de Windows (Windows-1252 / ISO-8859-1)
     * y removiendo marcas de orden de bytes (BOM).
     */
    private function cleanUtf8(mixed $data): mixed
    {
        if (is_string($data)) {
            // Eliminar BOM de UTF-8 si existe al inicio
            $data = preg_replace('/^\xEF\xBB\xBF/', '', $data);
            if (!mb_check_encoding($data, 'UTF-8')) {
                $converted = @mb_convert_encoding($data, 'UTF-8', 'Windows-1252, ISO-8859-1, ASCII');
                if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                    return $converted;
                }
                return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            }
            return $data;
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $k => $v) {
                $cleanKey = is_string($k) ? $this->cleanUtf8($k) : $k;
                $result[$cleanKey] = $this->cleanUtf8($v);
            }
            return $result;
        }

        if (is_object($data)) {
            foreach ($data as $k => $v) {
                $data->{$k} = $this->cleanUtf8($v);
            }
            return $data;
        }

        return $data;
    }

    /**
     * Normaliza cualquier variante de valor booleano o bandera (CSV / Excel / BD) a 0 o 1.
     * Mapea afirmativos: '1', 'S', 'SI', 'SÍ', 'YES', 'Y', 'TRUE', 'VERDADERO', 'V', 'ACTIVO', 'ACTIVA', 'HABILITADO', 'HABILITADA', 'ON' => 1
     * Mapea negativos/otros: 'N', 'NO', 'FALSO', 'FALSE', '0', 'INACTIVO', 'INACTIVA', vacío, null => 0
     */
    private function parseBooleanValue(mixed $val): int
    {
        if (is_bool($val)) {
            return $val ? 1 : 0;
        }

        if (is_numeric($val)) {
            return ((int)$val !== 0) ? 1 : 0;
        }

        $clean = mb_strtoupper(trim((string)$val), 'UTF-8');
        if ($clean === '') {
            return 0;
        }
        $clean = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $clean);

        $trueValues = [
            '1', 'S', 'SI', 'YES', 'Y', 'TRUE', 'VERDADERO', 'V',
            'ACTIVO', 'ACTIVA', 'HABILITADO', 'HABILITADA', 'ON'
        ];

        return in_array($clean, $trueValues, true) ? 1 : 0;
    }

    /**
     * Retorna los recursos necesarios para el mapeo de columnas (Normalización y diccionarios)
     */
    private function getMappingResources()
    {
        $normalize = function($str) {
            if (!$str) return '';
            $str = $this->cleanUtf8($str);
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
            $normalize('Característica')  => 'descripcion',
            $normalize('Caracteristica')  => 'descripcion',
            $normalize('U.M.')            => 'unidad_medida',
            $normalize('Unidad_Medida')   => 'unidad_medida',
            $normalize('Unid. Med.')      => 'unidad_medida',
            $normalize('Unidad Medida')   => 'unidad_medida',
            $normalize('UNIDADDEMEDIDA')  => 'unidad_medida',
            $normalize('Línea')           => 'linea',
            $normalize('Linea')           => 'linea',
            $normalize('Clasificación')   => 'clasificacion',
            $normalize('Clasificacion')   => 'clasificacion',
            $normalize('MN/USD')          => 'mn_usd',
            $normalize('MN_USD')          => 'mn_usd',
            $normalize('M/U')             => 'mn_usd',
            $normalize('MONEDA')          => 'mn_usd',
            $normalize('Moneda')          => 'mn_usd',
            $normalize('P. Lista')        => 'precio_lista',
            $normalize('Precio_Lista')    => 'precio_lista',
            $normalize('Precio Lista')    => 'precio_lista',
            $normalize('PRECIOLISTA')     => 'precio_lista',
            $normalize('P. Venta')        => 'precio_venta',
            $normalize('Precio_Venta')    => 'precio_venta',
            $normalize('Precio Venta')    => 'precio_venta',
            $normalize('PRECIOVENTA')     => 'precio_venta',
            $normalize('Desc. P. Venta')  => 'des_precio_venta',
            $normalize('Desc. P. Venta (Auto)') => 'des_precio_venta',
            $normalize('Desc. P. Venta (Calculado)') => 'des_precio_venta',
            $normalize('% Desc. V')       => 'des_precio_venta',
            $normalize('Desc_Precio_Venta') => 'des_precio_venta',
            $normalize('DESCUENTOPRECIOVTA') => 'des_precio_venta',
            $normalize('P. Especial')     => 'precio_especial',
            $normalize('P. Especial (Auto)') => 'precio_especial',
            $normalize('P. Especial (Calculado)') => 'precio_especial',
            $normalize('P. Espec.')       => 'precio_especial',
            $normalize('Precio_Especial') => 'precio_especial',
            $normalize('Precio Especial') => 'precio_especial',
            $normalize('Precio Especial (Auto)') => 'precio_especial',
            $normalize('Precio Especial (Calculado)') => 'precio_especial',
            $normalize('PRECIOVTAESPECIAL') => 'precio_especial',
            $normalize('Desc. P. Espec')  => 'desc_precio_espec',
            $normalize('% Desc. E')       => 'desc_precio_espec',
            $normalize('Desc_Precio_Espec') => 'desc_precio_espec',
            $normalize('DESCUENTOESPECIAL') => 'desc_precio_espec',
            $normalize('Precio 4')        => 'precio4',
            $normalize('Precio 4 (Auto)') => 'precio4',
            $normalize('Precio 4 (Calculado)') => 'precio4',
            $normalize('Precio4')         => 'precio4',
            $normalize('Precio4 (Auto)')  => 'precio4',
            $normalize('PRECIO4')         => 'precio4',
            $normalize('Desc. Precio 4')  => 'desc_precio4',
            $normalize('% Desc. 4')       => 'desc_precio4',
            $normalize('Desc_Precio4')    => 'desc_precio4',
            $normalize('DESCUENTOPRECIO4') => 'desc_precio4',
            $normalize('Costo Venta')     => 'costo_venta',
            $normalize('Costo de Venta')  => 'costo_venta',
            $normalize('CostoVenta')      => 'costo_venta',
            $normalize('Costo')           => 'costo_venta',
            $normalize('% Descuento')     => 'porcetaje_descuento',
            $normalize('Descuento')       => 'porcetaje_descuento',
            $normalize('PorcentajeDescuento') => 'porcetaje_descuento',
            $normalize('Porcentaje de Descuento') => 'porcetaje_descuento',
            $normalize('Art. Kit')        => 'articulo_kit',
            $normalize('Kit')             => 'articulo_kit',
            $normalize('KIT')             => 'articulo_kit',
            $normalize('Articulo_Kit')    => 'articulo_kit',
            $normalize('Art. Serie')      => 'articulo_serie',
            $normalize('Serie')           => 'articulo_serie',
            $normalize('SERIES')          => 'articulo_serie',
            $normalize('Series')          => 'articulo_serie',
            $normalize('Articulo_Serie')  => 'articulo_serie',
            $normalize('Mg Mín')          => 'margen_minimo',
            $normalize('Margen')          => 'margen_minimo',
            $normalize('MARGENMINIMO')    => 'margen_minimo',
            $normalize('Margen_Minimo')   => 'margen_minimo',
            $normalize('Color')           => 'color',
            $normalize('COLOR')           => 'color',
            $normalize('Protocolo')       => 'protocolo',
            $normalize('PROTOCOLO')       => 'protocolo',
            $normalize('Prot.')           => 'protocolo',
            $normalize('IDSAT')           => 'idsat',
            $normalize('SAT')             => 'idsat',
            $normalize('Estatus')         => 'habilitado',
            $normalize('Estatus Global')  => 'habilitado',
            $normalize('Habilitado')      => 'habilitado',
            $normalize('HABILIDATO')      => 'habilitado',
            $normalize('Habilidato')      => 'habilitado',
            $normalize('Area')            => 'area',
            $normalize('IVA')             => 'iva',
            $normalize('Ubicacion')       => 'ubicacion',
            $normalize('Ubicación')       => 'ubicacion',
            $normalize('UBICACION')       => 'ubicacion',
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
            $normalize('DESCTOPROVEEDOR') => 'desc_proveedor',
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
            $normalize('PESO')            => 'peso',
            $normalize('Std Pack')        => 'std_pack',
            $normalize('StdPack')         => 'std_pack',
            $normalize('Std_Pack')        => 'std_pack',
            $normalize('STANDARDPACK')    => 'std_pack',
            $normalize('Empaque')         => 'std_pack',
            $normalize('Crítico')         => 'critico',
            $normalize('Critico')         => 'critico',
            $normalize('CRITICO')         => 'critico',
            $normalize('Control Pedimentos') => 'control_pedimentos',
            $normalize('Control_Pedimentos') => 'control_pedimentos',
            $normalize('Pedimentos')      => 'control_pedimentos',
            $normalize('PEDIMIENTOS')     => 'control_pedimentos',
            $normalize('Pedimientos')     => 'control_pedimentos',
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
            // Paso 4: Área solo se usa al crear artículos desde cero (no actualiza los existentes)
            'Clave', 'Descripción', 'U.M.', 'Línea', 'Clasificación', 'Área', 'MN/USD',
            'P. Lista', 'Desc. P. Venta (Auto)', 'P. Venta', 'Desc. P. Espec', 'P. Especial (Auto)',
            'Desc. Precio 4', 'Precio 4 (Auto)', 'Desc. Proveedor', 'Art. Kit', 'Mg Mín',
            'Std Pack', 'Art. Serie', 'Color', 'Ubicacion', 'Control Pedimentos', 'Protocolo',
            'IDSAT', 'Peso', 'Crítico', 'Estatus', 'Sustituto', 'Sustituto 1', 'Sustituto 2',
            'Costo Venta', '% Descuento', 'Precio Tope (Auto)',
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

            // Parte 2 machotes: dato vacío en el maestro → celda vacía (al subir, vacía = conservar
            // el valor actual de cada sucursal). Antes se inventaban valores (Ubicación GENERAL,
            // Color 0, Área 1…) que al subir el machote pisaban lo propio de cada sucursal.
            $txt = fn ($v) => ($v === null || trim((string) $v) === '') ? '' : $v;
            $num = fn ($v) => ($v === null || trim((string) $v) === '') ? '' : number_format((float) $v, 2, '.', '');

            $formatRow = function ($art) use ($txt, $num) {
                return [
                    $art->clave,
                    $txt($art->descripcion),
                    $txt($art->unidad_medida),
                    $txt($art->linea),
                    $txt($art->clasificacion),
                    $txt($art->area),
                    $txt($art->mn_usd),
                    $num($art->precio_lista),
                    $num($art->des_precio_venta),
                    $num($art->precio_venta),
                    $num($art->desc_precio_espec),
                    $num($art->precio_especial),
                    $num($art->desc_precio4),
                    $num($art->precio4),
                    $num($art->desc_proveedor),
                    $txt($art->articulo_kit),
                    $num($art->margen_minimo),
                    $num($art->std_pack),
                    $txt($art->articulo_serie),
                    $txt($art->color),
                    $txt($art->ubicacion),
                    $txt($art->control_pedimentos),
                    $txt($art->protocolo),
                    $txt($art->idsat),
                    $num($art->peso),
                    $txt($art->critico),
                    $art->habilitado === null || $art->habilitado === '' ? '' : ($art->habilitado ? 'ACTIVO' : 'INACTIVO'),
                    $txt($art->sustituto),
                    $txt($art->sustituto1),
                    $txt($art->sustituto2),
                    $num($art->costo_venta),
                    $num($art->porcetaje_descuento),
                    $num($art->precio_tope),
                ];
            };

            if ($tipo === 'catalogo') {
                // Exportar catálogo maestro formateado a 2 decimales en todos los precios
                $articles = DbMasterArticle::take(1000)->get();
                foreach ($articles as $art) {
                    fputcsv($handle, $formatRow($art));
                }
            } else {
                // con_datos: intentamos obtener artículos reales de la BD primero
                $articles = DbMasterArticle::take(5)->get();

                if ($articles->count() > 0) {
                    foreach ($articles as $art) {
                        fputcsv($handle, $formatRow($art));
                    }
                } else {
                    // Si la BD no tiene registros, exportar ejemplos reales representativos de un ERP
                    $example1 = [
                        'HERR-00845', 'MARTILLO DE UÑA CURVA 16 OZ MANGO DE FIBRA DE VIDRIO', 'PZA', 'HERR', 'MANU', '1', '0',
                        '285.00', '10.00', '256.50', '15.00', '242.25',
                        '20.00', '228.00', '12.50', '0', '18.00',
                        '6.00', '0', '0', 'EST-A1-04', '0', '0',
                        '27111602', '0.65', '0', 'ACTIVO', 'HERR-00846', '0', '0',
                        '145.00', '25.00', '213.75',
                    ];
                    $example2 = [
                        'ELEC-01290', 'CABLE THW-LS CALIBRE 12 AWG 600V COLOR NEGRO', 'MTR', 'ELEC', 'CABL', '1', '0',
                        '18.50', '8.00', '17.02', '12.00', '16.28',
                        '15.00', '15.73', '10.00', '0', '15.00',
                        '100.00', '0', '1', 'BOD-E3-12', '1', '0',
                        '26121500', '0.04', '1', 'ACTIVO', 'ELEC-01291', 'ELEC-01292', '0',
                        '9.80', '20.00', '14.80',
                    ];
                    $example3 = [
                        'KIT-00501', 'JUEGO DE HERRAMIENTAS INALÁMBRICAS 20V MAX (DRILL + IMPACT)', 'JGO', 'ELEC', 'KITS', '1', '1',
                        '3490.00', '12.00', '3071.20', '18.00', '2861.80',
                        '22.00', '2722.20', '15.00', '1', '25.00',
                        '1.00', '1', '0', 'MOSTRADOR', '1', '1',
                        '27112700', '4.80', '1', 'ACTIVO', 'KIT-00502', '0', '0',
                        '1850.00', '30.00', '2443.00',
                    ];

                    fputcsv($handle, $example1);
                    fputcsv($handle, $example2);
                    fputcsv($handle, $example3);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Carga masiva: valida, guarda el archivo, crea la subida en el historial y lanza el
     * proceso en segundo plano (`unidata:carga-masiva`). El proceso de fondo no tiene el
     * límite de 120 s del servidor web; la pantalla consulta el avance con estadoSubida().
     */
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

        // Validar que se haya ejecutado la vista previa obligatoria para este archivo
        $uploadedHash = md5_file($file->getRealPath());
        if ($request->session()->get('preview_completed_hash') !== $uploadedHash) {
            return response()->json([
                'success' => false,
                'message' => 'Es obligatorio generar y revisar la Vista Previa antes de ejecutar la actualización.'
            ], 422);
        }

        // Una sola carga masiva a la vez
        $estado = $this->leerEstadoCarga();
        if ($this->cargaEnCurso($estado)) {
            return response()->json([
                'success'  => false,
                'en_curso' => true,
                'message'  => 'Ya hay una carga masiva en proceso (subida #' . ($estado['historial_id'] ?? '?') . ', fila '
                    . ($estado['step'] ?? 0) . ' de ' . ($estado['total'] ?? 0) . '). Espera a que termine o cancélala.',
            ], 409);
        }

        try {
            // Guardar el archivo físicamente para futuras descargas (y para que lo lea el proceso de fondo)
            $storedFile = $file->store('uploads/csv_history');

            $totalFilas = 0;
            $h = fopen(Storage::disk('local')->path($storedFile), 'r');
            if ($h) {
                fgets($h); // encabezado
                while (($linea = fgets($h)) !== false) {
                    if (trim($linea) !== '') $totalFilas++;
                }
                fclose($h);
            }

            // ── INICIAR REGISTRO DE HISTORIAL ──
            $historialId = DB::table('csv_historial')->insertGetId([
                'archivo_nombre'      => $file->getClientOriginalName(),
                'archivo_path'        => $storedFile, // Ruta relativa para descarga
                'articulos_afectados' => 0,
                'sucursales_json'     => json_encode($branchesSelected),
                'fecha'               => now()
            ]);

            $paramsFile = self::cargaParamsFile($historialId);
            if (!is_dir(dirname($paramsFile))) {
                mkdir(dirname($paramsFile), 0775, true);
            }
            // cleanUtf8: nombres de columna con acento que lleguen en Windows-1252 harían fallar json_encode
            file_put_contents($paramsFile, json_encode($this->cleanUtf8([
                'archivo_path'   => $storedFile,
                'archivo_nombre' => $file->getClientOriginalName(),
                'branches'       => array_values($branchesSelected),
                'columns'        => array_values($columnsSelected),
                'total_filas'    => $totalFilas,
                'usuario_id'     => auth()->id(),
            ]), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            $this->escribirEstadoCarga([
                'status'       => 'running',
                'historial_id' => $historialId,
                'archivo'      => $this->cleanUtf8($file->getClientOriginalName()),
                'message'      => 'Iniciando carga...',
                'step'         => 0,
                'total'        => $totalFilas,
                'procesados'   => 0,
                'started_at'   => time(),
            ]);

            // Lanzar proceso de fondo en Windows/XAMPP (mismo patrón que Homologación y Catálogo Maestro)
            $php     = PHP_BINARY;
            $artisan = base_path('artisan');
            $log     = storage_path('logs') . DIRECTORY_SEPARATOR . 'carga_masiva_bg.log';
            $cmd     = 'start "" /B "' . $php . '" "' . $artisan . '" unidata:carga-masiva ' . (int) $historialId . ' >> "' . $log . '" 2>&1';
            pclose(popen($cmd, 'r'));

            $request->session()->forget('preview_completed_hash');

            return response()->json([
                'success'      => true,
                'started'      => true,
                'historial_id' => $historialId,
                'total'        => $totalFilas,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo iniciar la carga: ' . $this->cleanUtf8($e->getMessage())
            ], 500);
        }
    }

    /** Avance de la carga masiva en segundo plano (la pantalla lo consulta cada pocos segundos). */
    public function estadoSubida()
    {
        $estado = $this->leerEstadoCarga();
        if (!$estado) {
            return response()->json(['status' => 'idle']);
        }
        // Proceso que dejó de reportar avance (p.ej. se reinició el servidor)
        if (($estado['status'] ?? '') === 'running' && !$this->cargaEnCurso($estado)) {
            $estado['status']  = 'stalled';
            $estado['message'] = 'El proceso dejó de responder. Revisa la subida #' . ($estado['historial_id'] ?? '?') . ' en el historial.';
        }
        return response()->json($estado);
    }

    /** Pide detener la carga; el proceso se detiene al terminar el artículo en curso. */
    public function cancelarSubida()
    {
        $estado = $this->leerEstadoCarga();
        if ($estado && ($estado['status'] ?? '') === 'running') {
            $estado['cancel_requested'] = true;
            $estado['message'] = 'Cancelando... se detendrá al terminar el artículo en curso.';
            $this->escribirEstadoCarga($estado);
        }
        return response()->json(['success' => true]);
    }

    public static function cargaStatusFile(): string
    {
        return storage_path('app/carga_masiva_status.json');
    }

    public static function cargaParamsFile(int $historialId): string
    {
        return storage_path('app/cargas_masivas/' . $historialId . '_params.json');
    }

    public function leerEstadoCarga(): ?array
    {
        $f = self::cargaStatusFile();
        if (!file_exists($f)) return null;
        $data = json_decode((string) @file_get_contents($f), true);
        return is_array($data) ? $data : null;
    }

    public function escribirEstadoCarga(array $estado): void
    {
        $estado['updated_at'] = time();
        file_put_contents(self::cargaStatusFile(), json_encode($this->cleanUtf8($estado), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), LOCK_EX);
    }

    /** Corriendo = status running y reportó avance en los últimos 10 minutos. */
    private function cargaEnCurso(?array $estado): bool
    {
        return $estado
            && ($estado['status'] ?? '') === 'running'
            && (time() - (int) ($estado['updated_at'] ?? 0)) < 600;
    }

    /**
     * Ciclo de la carga masiva. Lo ejecuta el comando `unidata:carga-masiva` en segundo plano.
     * Misma lógica por artículo que antes; solo se agregó avance y cancelación entre filas.
     *
     * @param callable $avance    fn(int $filasLeidas, int $procesados): void
     * @param callable $cancelado fn(): bool
     */
    public function ejecutarCargaMasiva(int $historialId, callable $avance, callable $cancelado): array
    {
        $params = json_decode((string) @file_get_contents(self::cargaParamsFile($historialId)), true);
        if (!is_array($params)) {
            return ['success' => false, 'procesados' => 0, 'message' => "No se encontraron los datos de la subida #{$historialId}."];
        }
        $branchesSelected = $params['branches'];
        $columnsSelected  = $params['columns'];
        $updatedCount     = 0;
        $canceladoEnFila  = null;

        [$normalize, $standardMapping, $branchFieldMap] = $this->getMappingResources();

        try {
            $fullPath = Storage::disk('local')->path($params['archivo_path']);

            $handle = fopen($fullPath, "r");
            if (!$handle) throw new \Exception("No se pudo abrir el archivo guardado.");

            $rawHeaderLine = fgets($handle);
            if (!$rawHeaderLine) {
                fclose($handle);
                throw new \Exception("El archivo subido está completamente vacío.");
            }

            $delimiter = (str_contains($rawHeaderLine, ';')) ? ';' : ',';
            rewind($handle);
            $headerOriginal = fgetcsv($handle, 0, $delimiter);

            if (!$headerOriginal || empty($headerOriginal)) {
                fclose($handle);
                throw new \Exception("No se pudieron leer los encabezados del archivo CSV.");
            }

            // Sanitizar encabezados y remover BOM
            $headerOriginal = array_map([$this, 'cleanUtf8'], $headerOriginal);
            if (!empty($headerOriginal)) {
                $headerOriginal[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headerOriginal[0]);
            }

            // Mapear qué índice del CSV corresponde a qué campo de BD
            $headerMap = [];
            foreach ($headerOriginal as $index => $h) {
                $normH = $normalize($h);
                if (isset($standardMapping[$normH])) {
                    $headerMap[$index] = $standardMapping[$normH];
                }
            }

            if (!in_array('clave', $headerMap)) {
                fclose($handle);
                return [
                    'success' => false,
                    'procesados' => 0,
                    'message' => $this->mensajeSinClave($headerMap, $headerOriginal),
                ];
            }

            $branchesMap = $this->connectionManager->getActiveBranches()->pluck('name', 'code')->toArray();
            $branchStats = [];
            foreach ($branchesSelected as $suc) {
                if (isset($branchesMap[$suc])) {
                    $branchStats[$suc] = ['ok' => 0, 'fail' => 0, 'errors' => []];
                }
            }

            // El registro de historial ya lo creó procesarSubida() antes de lanzar el proceso de fondo
            // AU-01: nombre de columna tal como viene en el CSV, para los avisos
            $headerLabel = [];
            foreach ($headerMap as $idx => $mappedField) {
                $headerLabel[$mappedField] = $headerOriginal[$idx];
            }
            $filasInvalidas = [];
            $clavesNoEncontradas = []; // AU-02
            $fila = 1; // la fila 1 es el encabezado

            // Cambio 2: resultado por fila (se guarda en storage/app/cargas_masivas/{id}_resultados.csv)
            $resultados = [];
            $pendientesClasificar = []; // índices de $resultados que no están en el maestro
            $registrar = function (int $f, string $claveCsv, string $desc, string $motivo, string $detalle = '') use (&$resultados, $historialId) {
                $resultados[] = ['fila' => $f, 'clave' => $claveCsv, 'descripcion' => $desc, 'motivo' => $motivo, 'detalle' => $detalle];
                if (count($resultados) % 500 === 0) {
                    $this->guardarResultadosCarga($historialId, $resultados);
                }
            };

            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                // Carga en segundo plano: avance y cancelación entre filas (nunca a la mitad de un artículo)
                if ($cancelado()) {
                    $canceladoEnFila = $fila;
                    break;
                }
                $avance($fila - 1, $updatedCount);

                $fila++;
                $row = array_map([$this, 'cleanUtf8'], $row);
                $item = [];
                foreach ($row as $index => $value) {
                    if (isset($headerMap[$index])) {
                        $value = trim((string)$value);
                        // Celda vacía = conservar el valor actual (no borra ni pone 0); la clave sí se toma
                        if ($value === '' && $headerMap[$index] !== 'clave') continue;
                        $item[$headerMap[$index]] = $value;
                    }
                }

                $rawClave = $item['clave'] ?? null;
                if (!$rawClave) {
                    if (trim(implode('', $row)) !== '') {
                        $registrar($fila, '', (string)($item['descripcion'] ?? ''), 'sin_clave');
                    }
                    continue;
                }

                // Normalizar clave eliminando guiones y espacios para que funcione acorde a la BD sin guiones
                $clave = strtoupper(str_replace(['-', ' '], '', trim((string)$rawClave)));
                $item['clave'] = $clave;
                $claveCsv    = trim((string)$rawClave);
                $descCsv     = (string)($item['descripcion'] ?? '');
                $erroresFila = [];

                $updateDataMaster = [];
                $updateDataBranch = [];
                $createData = [];   // Paso 4: datos para crear desde cero (columnas seleccionadas + obligatorias, incl. Área)
                $filaConError = false;

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
                    // Paso 4: los obligatorios para crear (p. ej. Área, que no es columna de la pantalla) se leen
                    // aunque no estén seleccionados, pero SOLO se usan si la clave se crea desde cero
                    $soloParaCrear = !$foundInUi && in_array($field, self::CAMPOS_CREAR, true);

                    if ($foundInUi || $soloParaCrear) {
                        // 1. Manejo de booleanos (excluyendo color que es un entero de 0 a 9)
                        $booleanFields = ['habilitado', 'articulo_kit', 'articulo_serie', 'en_promocion', 'critico', 'control_pedimentos', 'protocolo'];
                        if (in_array($field, $booleanFields, true)) {
                            $val = $this->parseBooleanValue($val);
                        } elseif ($field === 'mn_usd') {
                            $val = in_array(strtoupper(trim((string)$val)), ['1', 'U', 'USD', 'DLLS'], true) ? 'U' : 'M';
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
                        if (in_array($field, $this->columnasNumericasCsv(), true)) {
                            // AU-01: "1,234.50" se acepta como 1234.50; texto como "abc" ya no se guarda como 0
                            [$esNumero, $numero] = $this->parseNumeroCsv($val);
                            if (!$esNumero && $soloParaCrear) continue;   // se reporta como "falta" si hay que crear
                            if (!$esNumero) {
                                $filaConError = true;
                                $filasInvalidas[] = "Fila {$fila} ({$clave}): " . ($headerLabel[$field] ?? $field) . " = '{$val}' no es un número";
                                $erroresFila[] = ($headerLabel[$field] ?? $field) . " = '{$val}'";
                                continue;
                            }
                            $val = $numero;
                        }

                        $createData[$field] = $val;
                        if ($soloParaCrear) continue;   // no toca artículos existentes

                        // Para el Maestro (snake_case estándar)
                        $updateDataMaster[$field] = $val;

                        // Para las Sucursales (PascalCase / Original)
                        if (isset($branchFieldMap[$field])) {
                            $updateDataBranch[$branchFieldMap[$field]] = $val;
                        }
                    }
                }

                // AU-01: si la fila trae algún número inválido, no se guarda nada de esa fila
                if ($filaConError) {
                    $registrar($fila, $claveCsv, $descCsv, 'no_numerico', implode('; ', $erroresFila));
                    continue;
                }

                if (empty($updateDataMaster)) {
                    $registrar($fila, $claveCsv, $descCsv, 'sin_datos');
                    continue;
                }

                // Cambio 2: un error en una fila se anota y la carga sigue con la siguiente
                $fallosFila = [];
                $noExisteEn = [];
                try {

                // --- GOBERNANZA: SOLO ACTUALIZACIONES ---
                // Buscar por clave exacta o con fallback normalizado (sin guiones, espacios ni caracteres especiales)
                $masterCurrent = DbMasterArticle::where('clave', $clave)->first();
                if (!$masterCurrent) {
                    $cleanClave = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$clave));
                    if ($cleanClave !== '') {
                        $masterCurrent = DbMasterArticle::where('clave', $cleanClave)
                            ->orWhereRaw("REPLACE(REPLACE(REPLACE(clave, '-', ''), ' ', ''), '_', '') = ?", [$cleanClave])
                            ->first();
                    }
                }
                // Paso 3: si existe activa en alguna sucursal, crearla donde falta y darla de alta en el maestro
                $altaFila = null;
                $branchesFila = $branchesSelected;
                if (!$masterCurrent) {
                    $altaFila = $this->crearDondeFalta((string)$clave, $updateDataBranch, $historialId, $createData);
                    if (!empty($altaFila['master'])) {
                        $masterCurrent = $altaFila['master'];
                        // Las sucursales recién creadas también reciben las fórmulas de esta fila
                        $nombreACodigo = array_flip($branchesMap);
                        foreach ($altaFila['creadas'] as $nombreSuc) {
                            if (!isset($nombreACodigo[$nombreSuc])) continue;
                            $codSuc = $nombreACodigo[$nombreSuc];
                            $branchesFila[] = $codSuc;
                            if (!isset($branchStats[$codSuc])) $branchStats[$codSuc] = ['ok' => 0, 'fail' => 0, 'errors' => []];
                        }
                        $branchesFila = array_values(array_unique($branchesFila));
                    } elseif (!empty($altaFila['error'])) {
                        $registrar($fila, $claveCsv, $descCsv, 'error', $this->cleanUtf8($altaFila['detalle']));
                        continue;
                    } elseif (!empty($altaFila['motivo'])) {
                        // Regla de línea / paso 4: omitida con su motivo, sin tocar nada
                        $registrar($fila, $claveCsv, $descCsv, $altaFila['motivo'], $this->cleanUtf8($altaFila['detalle'] ?? ''));
                        continue;
                    }
                }
                if (!$masterCurrent) {
                    // AU-02: la carga masiva no crea artículos; reportar la clave en lugar de omitirla en silencio
                    $clavesNoEncontradas[] = "Fila {$fila} ({$clave})";
                    $pendientesClasificar[] = count($resultados);
                    $registrar($fila, $claveCsv, $descCsv, 'no_existe'); // motivo final al terminar
                    continue;
                }

                $canonicalClave = $masterCurrent->clave;

                // --- AUTO-CÁLCULO SIEMPRE DE CAMPOS DERIVADOS VÍA FÓRMULA (2 DECIMALES) ---
                $pLista = isset($updateDataMaster['precio_lista']) ? (float)$updateDataMaster['precio_lista'] : (float)($masterCurrent->precio_lista ?? 0);
                $pVenta = isset($updateDataMaster['precio_venta']) ? (float)$updateDataMaster['precio_venta'] : (float)($masterCurrent->precio_venta ?? 0);
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

                // Auto-cálculo de Desc. P. Venta a partir de P.Lista y P.Venta
                $calcDescVenta = \App\Support\ArticuloValidacion::descPrecioVenta($pLista, $pVenta); // AU-04
                $updateDataMaster['des_precio_venta'] = $calcDescVenta;
                $updateDataBranch['Desc_Precio_Venta'] = $calcDescVenta;

                // --- AUDITORÍA ANTES DE ACTUALIZAR ---
                $auditEntries = [];
                if ($masterCurrent) {
                    foreach ($updateDataMaster as $field => $newVal) {
                        $oldVal = $masterCurrent->{$field};
                        $isDiff = false;
                        if (in_array($field, ['habilitado', 'articulo_kit', 'articulo_serie', 'en_promocion', 'critico', 'control_pedimentos', 'protocolo'], true)) {
                            $isDiff = $this->parseBooleanValue($oldVal) !== $this->parseBooleanValue($newVal);
                        } else {
                            $isDiff = $this->valorCambio($oldVal, $newVal);
                        }

                        if ($isDiff) {
                            $auditEntries[] = [
                                'historial_id' => $historialId,
                                'clave' => $canonicalClave,
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
                    foreach ($branchesFila as $suc) {
                        if (isset($branchesMap[$suc])) {
                            try {
                                $connection = $this->connectionManager->connect($suc);

                                $sucCurrent = $connection->table('articulo')->where('Clave_Articulo', $canonicalClave)->first();
                                if (!$sucCurrent && $clave !== $canonicalClave) {
                                    $sucCurrent = $connection->table('articulo')->where('Clave_Articulo', $clave)->first();
                                }

                                if ($sucCurrent) {
                                    $sucClaveReal = $sucCurrent->Clave_Articulo;
                                    foreach ($updateDataBranch as $fieldPascal => $newVal) {
                                        $oldVal = $sucCurrent->{$fieldPascal} ?? null;
                                        $fieldSnake = array_search($fieldPascal, $branchFieldMap, true) ?: $fieldPascal;
                                        $isDiff = false;
                                        if (in_array($fieldSnake, ['habilitado', 'articulo_kit', 'articulo_serie', 'en_promocion', 'critico', 'control_pedimentos', 'protocolo'], true)) {
                                            $isDiff = $this->parseBooleanValue($oldVal) !== $this->parseBooleanValue($newVal);
                                        } else {
                                            $isDiff = $this->valorCambio($oldVal, $newVal);
                                        }

                                        if ($isDiff) {
                                            $auditEntries[] = [
                                                'historial_id' => $historialId,
                                                'clave' => $sucClaveReal,
                                                'columna' => $fieldPascal,
                                                'valor_anterior' => (string)$oldVal,
                                                'valor_nuevo' => (string)$newVal,
                                                'sucursal' => $suc
                                            ];
                                        }
                                    }
                                    // Actualizar Sucursal
                                    $connection->table('articulo')
                                        ->where('Clave_Articulo', $sucClaveReal)
                                        ->update($updateDataBranch);
                                } else {
                                    $noExisteEn[] = $branchesMap[$suc] ?? $suc;
                                }
                                $branchStats[$suc]['ok']++;
                            } catch (Throwable $e) {
                                $fallosFila[] = ($branchesMap[$suc] ?? $suc) . ': ' . $this->friendlyDbError($e);
                                $branchStats[$suc]['fail']++;
                                if (count($branchStats[$suc]['errors']) < 3) {
                                    $branchStats[$suc]['errors'][] = "{$canonicalClave}: " . $this->friendlyDbError($e);
                                }
                            }
                        }
                    }

                    // Guardar la auditoría ANTES de PowerSales: si algo falla o se detiene ahí,
                    // Revertir ya conoce el cambio (en la subida #76 un artículo quedó sin auditoría)
                    if (!empty($auditEntries)) {
                        DB::table('csv_historial_detalles')->insert($auditEntries);
                        $auditEntries = [];
                    }

                    // Sync a PowerSales (con la clave canónica del maestro)
                    // AU-03: mandar el artículo completo ya actualizado (como en Editar), no solo
                    // las columnas del CSV; si no, Descripción/Habilitado viajan vacíos.
                    $branchDataPs = array_merge(
                        \App\Support\ArticuloFieldMap::toBranchFormat($masterCurrent->fresh()->toArray()),
                        ['Clave_Articulo' => $canonicalClave],
                        $updateDataBranch
                    );
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

                $detalleFila = [];
                if (!empty($altaFila['master'])) {
                    if (!empty($altaFila['activadas'])) $detalleFila[] = 'Activado en: ' . implode(', ', $altaFila['activadas']);
                    if (($altaFila['accion'] ?? '') === 'creado_nuevo') {
                        $detalleFila[] = 'Creado desde cero en: ' . implode(', ', $altaFila['creadas']) . ' (datos del archivo)';
                    } elseif (!empty($altaFila['creadas'])) {
                        $detalleFila[] = 'Creado en: ' . implode(', ', $altaFila['creadas']) . ' (copiado de ' . $altaFila['fuente'] . ')';
                    } elseif (empty($altaFila['activadas'])) {
                        $detalleFila[] = 'Ya estaba activo en todas las sucursales';
                    }
                }
                if ($noExisteEn) $detalleFila[] = 'No existe en: ' . implode(', ', $noExisteEn);
                if ($fallosFila) $detalleFila[] = 'Falló en: ' . implode(' | ', $fallosFila);
                $registrar(
                    $fila,
                    $claveCsv,
                    $descCsv !== '' ? $descCsv : (string)($masterCurrent->descripcion ?? ''),
                    $fallosFila ? 'error_sucursal' : (!empty($altaFila['master']) ? $altaFila['accion'] : 'actualizado'),
                    implode('. ', $detalleFila)
                );
                } catch (Throwable $e) {
                    $registrar($fila, $claveCsv, $descCsv, 'error', $this->cleanUtf8($this->friendlyDbError($e)));
                    continue;
                }
            }

            // Cambio 2: al cancelar, las filas que faltaban quedan como "No procesado"
            if ($canceladoEnFila !== null) {
                $pendRow = $row;
                while ($pendRow !== false) {
                    $fila++;
                    $it = [];
                    foreach (array_map([$this, 'cleanUtf8'], $pendRow) as $i => $v) {
                        if (isset($headerMap[$i])) $it[$headerMap[$i]] = trim((string)$v);
                    }
                    if (($it['clave'] ?? '') !== '') {
                        $resultados[] = ['fila' => $fila, 'clave' => $it['clave'], 'descripcion' => (string)($it['descripcion'] ?? ''), 'motivo' => 'no_procesado', 'detalle' => 'La carga se canceló antes de llegar a esta fila'];
                    }
                    $pendRow = fgetcsv($handle, 0, $delimiter);
                }
            }
            fclose($handle);

            // Cambio 2: motivo de las claves que no están en el maestro, claves repetidas y resumen
            $avance($fila - 1, $updatedCount);
            $this->clasificarNoEncontradas($resultados, $pendientesClasificar, $branchesMap);
            $resumenCarga = $this->completarResultadosCarga($resultados);
            $this->guardarResultadosCarga($historialId, $resultados);

            // AU-01: resumen de filas omitidas por números inválidos (máximo 10 en el mensaje)
            $avisoInvalidas = '';
            if (!empty($filasInvalidas)) {
                $avisoInvalidas = ' Filas omitidas por valores no numéricos (' . count($filasInvalidas) . '): '
                    . implode(' | ', array_slice($filasInvalidas, 0, 10))
                    . (count($filasInvalidas) > 10 ? ' | ...' : '');
            }

            // AU-02: resumen de claves que no existen en el maestro (máximo 10 en el mensaje)
            if (!empty($clavesNoEncontradas)) {
                $avisoInvalidas .= ' Claves no encontradas en el Catálogo Maestro, omitidas (' . count($clavesNoEncontradas) . '): '
                    . implode(' | ', array_slice($clavesNoEncontradas, 0, 10))
                    . (count($clavesNoEncontradas) > 10 ? ' | ...' : '');
            }

            $avisoCancelado = $canceladoEnFila !== null
                ? ' Carga CANCELADA por el usuario después de la fila ' . $canceladoEnFila . '; las filas siguientes no se procesaron.'
                : '';

            if ($updatedCount === 0) {
                return $this->cleanUtf8([
                    'success' => false,
                    'cancelado' => $canceladoEnFila !== null,
                    'procesados' => 0,
                    'message' => ($canceladoEnFila !== null
                        ? 'Carga cancelada antes de actualizar algún artículo.'
                        : 'No se actualizó ningún artículo. Verifique que el archivo contenga filas de datos y que las claves coincidan con el Catálogo Maestro.') . $avisoInvalidas,
                    'filas_invalidas' => $filasInvalidas,
                    'claves_no_encontradas' => $clavesNoEncontradas,
                    'resumen' => $resumenCarga,
                ]);
            }

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

            $responsePayload = [
                'success' => true,
                'cancelado' => $canceladoEnFila !== null,
                'procesados' => $updatedCount,
                'message' => ($canceladoEnFila !== null ? 'Proceso detenido.' : 'Proceso completado.') . " Se procesaron {$updatedCount} artículos en el Maestro. Detalle por sucursal: " . implode(' | ', $branchDetail) . $avisoInvalidas . $avisoCancelado,
                'branch_stats' => $branchStats,
                'filas_invalidas' => $filasInvalidas,
                'claves_no_encontradas' => $clavesNoEncontradas,
                'resumen' => $resumenCarga,
            ];

            return $this->cleanUtf8($responsePayload);

        } catch (Throwable $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            // Dejar el conteo real en el historial aunque el proceso se haya detenido por error
            DB::table('csv_historial')->where('id', $historialId)->update(['articulos_afectados' => $updatedCount]);
            if (!empty($resultados)) {
                try { $this->guardarResultadosCarga($historialId, $resultados); } catch (Throwable $e2) { /* sin resultados */ }
            }
            return [
                'success' => false,
                'procesados' => $updatedCount,
                'message' => 'Error al procesar el archivo' . (isset($fila) ? " en la fila {$fila}" : '') . ': ' . $this->cleanUtf8($e->getMessage())
                    . " Se alcanzaron a procesar {$updatedCount} artículos (ver auditoría en el historial).",
            ];
        }
    }

    /** Cambio 3: tiempo medido por artículo en la carga masiva (3 llamadas a PowerSales incluidas). */
    public const SEGUNDOS_POR_ARTICULO = 2.3;

    /**
     * Cambio 3: cuántas filas del CSV tienen clave que existe en el Catálogo Maestro
     * (las que realmente se procesan). Mismo criterio que la carga: exacta o sin guiones/espacios/_.
     */
    private function contarEnMaestro(array $claves): int
    {
        if (empty($claves)) return 0;
        $enMaestro = $this->clavesEnMaestro($claves);
        $total = 0;
        foreach ($claves as $c) {
            if (isset($enMaestro[$c])) $total++;
        }
        return $total;
    }

    /** Claves (normalizadas a A-Z0-9) que sí están en el maestro, como conjunto [clave => true]. */
    private function clavesEnMaestro(array $claves): array
    {
        $enMaestro = [];
        foreach (array_chunk(array_values(array_unique($claves)), 1000) as $chunk) {
            $encontradas = DbMasterArticle::whereIn('clave', $chunk)
                ->orWhereIn(DB::raw("REPLACE(REPLACE(REPLACE(clave, '-', ''), ' ', ''), '_', '')"), $chunk)
                ->pluck('clave');
            foreach ($encontradas as $c) {
                $enMaestro[strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$c))] = true;
            }
        }
        return $enMaestro;
    }

    /** Paso 1: datos obligatorios para crear un artículo desde cero (los mismos que exige Crear Artículo). */
    public const CAMPOS_CREAR = ['descripcion', 'unidad_medida', 'linea', 'clasificacion', 'area', 'mn_usd'];
    private const ETIQUETAS_CREAR = [
        'descripcion' => 'Descripción', 'unidad_medida' => 'U.M.', 'linea' => 'Línea',
        'clasificacion' => 'Clasificación', 'area' => 'Área', 'mn_usd' => 'MN/USD',
    ];

    /**
     * Paso 1 (solo lectura): para las claves que no están en el maestro, consulta en las sucursales
     * activas si existen y si están activas, y decide qué hará la carga con cada una.
     * Devuelve [clave => ['accion' => código, 'tipo' => crear|inactivo|omitir, 'texto' => para la vista previa]].
     */
    private function accionesNoMaestro(array $claves, array $infoClaves): array
    {
        if (empty($claves)) return [];

        $branchesMap = $this->connectionManager->getActiveBranches()->pluck('name', 'code')->toArray();
        $info = [];          // clave => [codigo_sucursal => Habilitado]
        $sinRespuesta = [];
        foreach ($branchesMap as $code => $name) {
            try {
                $cn = $this->connectionManager->connect($code);
                foreach (array_chunk($claves, 500) as $chunk) {
                    foreach ($cn->table('articulo')->whereIn('Clave_Articulo', $chunk)->get(['Clave_Articulo', 'Habilitado']) as $r) {
                        $info[strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$r->Clave_Articulo))][$code] = (int)$r->Habilitado;
                    }
                }
            } catch (Throwable $e) {
                $sinRespuesta[] = $name;
            }
        }

        $out = [];
        $totalSuc = count($branchesMap);
        foreach ($claves as $k) {
            $en      = $info[$k] ?? [];
            $estatus = $infoClaves[$k]['estatus'] ?? null;   // 1, 0 o null (no viene / no seleccionado)
            $faltan  = $infoClaves[$k]['faltan'] ?? [];

            $crearEn = $inactivaEn = [];
            foreach ($branchesMap as $code => $name) {
                if (!array_key_exists($code, $en)) $crearEn[] = $name;
                elseif ($en[$code] !== 1) $inactivaEn[] = $name;
            }

            if ($sinRespuesta) {
                $acc = ['omitir', 'omitir', 'SE OMITIRÁ: sin respuesta de ' . implode(', ', $sinRespuesta)];
            } elseif (empty($en)) {
                if ($estatus === 0) {
                    $acc = ['omitir', 'omitir', 'SE OMITIRÁ: no existe y el CSV la marca inactiva'];
                } elseif ($faltan) {
                    $acc = ['omitir', 'omitir', 'SE OMITIRÁ: falta ' . implode(', ', array_map(fn ($f) => self::ETIQUETAS_CREAR[$f] ?? $f, $faltan))];
                } elseif (!is_numeric($areaCsv = $infoClaves[$k]['area'] ?? null) || !in_array((int) $areaCsv, $areasOk ??= $this->areasValidas(), true)) {
                    $acc = ['omitir', 'omitir', "SE OMITIRÁ: el Área '{$areaCsv}' no existe en el catálogo de áreas"];
                } else {
                    $acc = ['crear_nuevo', 'crear', "SE CREARÁ en las {$totalSuc} sucursales + maestro"];
                }
            } elseif ($estatus === 0) {
                // Paso 2 (opción b): si el CSV la marca inactiva no se activa ni se actualiza
                $acc = ['omitir', 'omitir', 'SE OMITIRÁ: el CSV la marca inactiva'];
            } elseif ($inactivaEn && $estatus === null) {
                $acc = ['omitir', 'omitir', 'SE OMITIRÁ: inactiva en ' . implode(', ', $inactivaEn) . ' y el CSV no indica Estatus'];
            } else {
                $partes = [];
                if ($crearEn)    $partes[] = 'SE CREARÁ en ' . implode(', ', $crearEn);
                if ($inactivaEn) $partes[] = 'SE ACTIVARÁ en ' . implode(', ', $inactivaEn);
                if ($partes) {
                    $codigo = $crearEn && $inactivaEn ? 'crear_activar' : ($crearEn ? 'crear_faltantes' : 'activar');
                    // Paso 2: las que se activan se marcan en ámbar (aviso), las que solo se crean en azul
                    $acc = [$codigo, $inactivaEn ? 'inactivo' : 'crear', implode(' y ', $partes) . ' + maestro'];
                } else {
                    $acc = ['alta_maestro', 'crear', 'SE AGREGARÁ al maestro (ya activa en todas las sucursales)'];
                }
            }

            // Regla de línea: lo que se daría de alta en el maestro se omite si su línea no es "Sí se pasa"
            if ($acc[0] !== 'omitir' && ($motivoLinea = $this->lineaNoAprobada($k)) !== null) {
                $acc = ['omitir', 'omitir', 'SE OMITIRÁ: ' . $motivoLinea];
            }

            $out[$k] = ['accion' => $acc[0], 'tipo' => $acc[1], 'texto' => $acc[2]];
            if (in_array($acc[0], ['activar', 'crear_activar'], true)) $out[$k]['inactiva_en'] = $inactivaEn;
        }
        return $out;
    }

    /** Regla de línea: cache de Homologación → Líneas por petición (linea => 'si'|'no'). */
    private ?array $lineasConfigCache = null;

    /**
     * Regla de línea (pasos 2, 3 y 4): la línea son los primeros 5 caracteres de la clave, igual que en
     * SyncMatrizHomologacion. Solo las líneas "Sí se pasa" entran a la matriz y de ahí al maestro, así que
     * la carga masiva no da de alta claves de otras líneas. Devuelve null si pasa o el texto del motivo.
     */
    private function lineaNoAprobada(string $clave): ?string
    {
        if ($this->lineasConfigCache === null) {
            $this->lineasConfigCache = \App\Models\HomologacionLineaConfig::pluck('tipo', 'linea')
                ->mapWithKeys(fn ($t, $l) => [strtoupper(trim((string) $l)) => (string) $t])->toArray();
        }
        $linea = strtoupper(substr(trim($clave), 0, 5));
        $tipo = $this->lineasConfigCache[$linea] ?? null;
        if ($tipo === 'si') return null;
        return $tipo === 'no'
            ? "la línea {$linea} está marcada \"No se pasa\" en Homologación"
            : "la línea {$linea} no está configurada en Homologación (Líneas)";
    }

    /** Paso 3: columnas propias de cada sucursal que NO se copian al crear el artículo en otra (columna => valor). */
    private const COLUMNAS_PROPIAS_SUCURSAL = [
        'Existencia_Teorica' => 0, 'Existencia_Fisica' => 0, 'Apartado' => 0, 'PendientedeEntrega' => 0,
        'Costo_Promedio' => 0, 'Costo_Promedio_Ant' => 0, 'Costo_Ult_Compra' => 0, 'Costo_Compra_Ant' => 0,
        'Fecha_Ult_Compra' => '1901-01-01', 'Fecha_Compra_Ant' => '1901-01-01',
        'Inventario_Maximo' => 0, 'Inventario_Minimo' => 0, 'Punto_Reorden' => 0, 'Ubicacion' => '',
    ];

    /**
     * Paso 3: la clave no está en el maestro pero existe (activa) en alguna sucursal. Copia el renglón de esa
     * sucursal a las sucursales activas donde falta (con los valores del CSV encima), da de alta el artículo
     * en el maestro y en la matriz, y deja la auditoría. Paso 2: si está inactiva en alguna sucursal y el CSV
     * trae Estatus activo, la activa ahí. No existe en ninguna (paso 4), CSV inactivo o CSV sin Estatus con
     * sucursales inactivas se devuelven sin tocar nada para que la fila se siga omitiendo.
     *
     * @return array{master: ?DbMasterArticle, accion?: string, creadas?: array, fuente?: string, detalle?: string}
     */
    private function crearDondeFalta(string $clave, array $updateDataBranch, int $historialId, array $createData = []): array
    {
        $k = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $clave));
        $branches = $this->connectionManager->getActiveBranches();

        $filas = [];   // codigo => renglón (array) donde existe
        $conns = [];
        foreach ($branches as $b) {
            try {
                $cn = $this->connectionManager->connect($b->code);
                $conns[$b->code] = $cn;
                $r = $cn->table('articulo')->where('Clave_Articulo', $k)->first();
                if (!$r && $clave !== $k) $r = $cn->table('articulo')->where('Clave_Articulo', $clave)->first();
                if ($r) $filas[$b->code] = (array) $r;
            } catch (Throwable $e) {
                return ['master' => null, 'detalle' => "Sin respuesta de {$b->name}; no se creó el artículo"];
            }
        }

        $nuevo = empty($filas);   // paso 4: no existe en ninguna sucursal → se crea desde cero con el CSV
        if (array_key_exists('Habilitado', $updateDataBranch) && (int) $updateDataBranch['Habilitado'] === 0) {
            return ['master' => null];                    // el CSV la marca inactiva (no entra al maestro)
        }
        // Regla de línea: no dar de alta claves cuya línea no es "Sí se pasa" (la sync de Homologación las sacaría)
        $motivoLinea = $this->lineaNoAprobada($nuevo ? $clave : (string) reset($filas)['Clave_Articulo']);
        if ($motivoLinea !== null) {
            return ['master' => null, 'motivo' => 'linea_no_aprobada', 'detalle' => 'No se dio de alta: ' . $motivoLinea];
        }
        $nombres = $branches->pluck('name', 'code')->toArray();

        if ($nuevo) {
            // Paso 4: renglón armado con valores por defecto (igual que Crear Artículo) + datos del CSV
            [$base, $faltaDetalle] = $this->renglonNuevoDesdeCsv($clave, $createData);
            if ($base === null) return ['master' => null, 'motivo' => 'faltan_datos', 'detalle' => $faltaDetalle];
            $inactivas = [];
            $codigoFuente = null;
            $claveReal = $clave;
        } else {
            // Paso 2: inactiva en alguna sucursal → solo se activa si el CSV trae Estatus activo
            $inactivas = array_keys(array_filter($filas, fn ($r) => (int) ($r['Habilitado'] ?? 0) !== 1));
            $csvActiva = array_key_exists('Habilitado', $updateDataBranch) && (int) $updateDataBranch['Habilitado'] === 1;
            if ($inactivas && !$csvActiva) return ['master' => null];   // el CSV no indica Estatus: se omite

            // Fuente para copiar: de preferencia una sucursal donde esté activa
            $activas = array_values(array_diff(array_keys($filas), $inactivas));
            $codigoFuente = $activas ? $activas[0] : array_key_first($filas);
            $fuente = $filas[$codigoFuente];
            $claveReal = (string) $fuente['Clave_Articulo'];

            // Renglón base: el de la sucursal fuente, sin lo propio de cada sucursal, con los valores del CSV encima
            $base = $fuente;
            foreach (self::COLUMNAS_PROPIAS_SUCURSAL as $col => $valor) {
                if (array_key_exists($col, $base)) $base[$col] = $valor;
            }
            $base['Habilitado'] = 1;
            $base['Fecha_Alta'] = now()->toDateString();
            $base = array_merge($base, $updateDataBranch, ['Clave_Articulo' => $claveReal, 'Habilitado' => 1]);
        }
        $origen = $nuevo ? 'el archivo' : ($nombres[$codigoFuente] ?? $codigoFuente);

        $creadas = [];
        $audit = [];
        foreach ($nombres as $code => $name) {
            if (isset($filas[$code])) continue;
            try {
                $cn = $conns[$code];
                // Solo las columnas que tiene la tabla de ESA sucursal, sin autoincrementables
                $columnas = [];
                $sinNulos = [];   // columnas NOT NULL de ESTA sucursal => valor de reemplazo
                foreach ($cn->select('SHOW COLUMNS FROM articulo') as $c) {
                    if (str_contains(strtolower((string) $c->Extra), 'auto_increment')) continue;
                    $columnas[$c->Field] = true;
                    if (strtoupper((string) $c->Null) === 'NO') {
                        $sinNulos[$c->Field] = $c->Default ?? $this->valorVacioColumna((string) $c->Type);
                    }
                }
                $renglon = array_intersect_key($base, $columnas);
                // El renglón copiado puede traer NULL (p. ej. Precio_gerente) donde esta sucursal no los acepta
                foreach ($renglon as $col => $v) {
                    if ($v === null && array_key_exists($col, $sinNulos)) $renglon[$col] = $sinNulos[$col];
                }
                $cn->table('articulo')->insert($renglon);
                $creadas[] = $name;
                $audit[] = [
                    'historial_id' => $historialId, 'clave' => $claveReal, 'columna' => 'TODAS (CREACIÓN)',
                    'valor_anterior' => 'NO EXISTÍA', 'valor_nuevo' => $nuevo ? 'CREADO (desde el archivo)' : 'CREADO (copiado de ' . $origen . ')',
                    'sucursal' => $code,
                ];
            } catch (Throwable $e) {
                if ($audit) DB::table('csv_historial_detalles')->insert($audit);
                $detalle = ($creadas ? 'Creado en: ' . implode(', ', $creadas) . '. ' : '')
                    . "Falló al crear en {$name}: " . $this->friendlyDbError($e) . '. No se dio de alta en el maestro';
                return ['master' => null, 'detalle' => $detalle, 'error' => true];
            }
        }

        // Paso 2: activar en las sucursales donde está inactiva (Revertir lo regresa a su valor anterior)
        $activadas = [];
        foreach ($inactivas as $code) {
            $name = $nombres[$code] ?? $code;
            try {
                $anterior = $filas[$code]['Habilitado'] ?? null;
                $conns[$code]->table('articulo')->where('Clave_Articulo', $filas[$code]['Clave_Articulo'])->update(['Habilitado' => 1]);
                $activadas[] = $name;
                $audit[] = [
                    'historial_id' => $historialId, 'clave' => $claveReal, 'columna' => 'Habilitado',
                    'valor_anterior' => $anterior === null ? null : (string) $anterior, 'valor_nuevo' => '1',
                    'sucursal' => $code,
                ];
            } catch (Throwable $e) {
                if ($audit) DB::table('csv_historial_detalles')->insert($audit);
                $detalle = ($creadas ? 'Creado en: ' . implode(', ', $creadas) . '. ' : '')
                    . ($activadas ? 'Activado en: ' . implode(', ', $activadas) . '. ' : '')
                    . "Falló al activar en {$name}: " . $this->friendlyDbError($e) . '. No se dio de alta en el maestro';
                return ['master' => null, 'detalle' => $detalle, 'error' => true];
            }
        }

        // Alta en el maestro con los datos del renglón base (fechas inválidas del ERP como null)
        $masterData = ['clave' => $claveReal];
        foreach (\App\Support\ArticuloFieldMap::map() as $snake => $pascal) {
            if (!array_key_exists($pascal, $base)) continue;
            $v = $base[$pascal];
            if (is_string($v) && str_starts_with($v, '0000-00-00')) $v = null;
            // Precios/descuentos NULL en la sucursal fuente (p. ej. Precio_gerente) → 0; las fórmulas los recalculan después
            if ($v === null && in_array($snake, $this->columnasNumericasCsv(), true)) $v = 0;
            $masterData[$snake] = $v;
        }
        $masterData['habilitado'] = 1;

        // Auditoría de lo creado en sucursales ANTES del alta en el maestro, por si esta falla
        if ($audit) DB::table('csv_historial_detalles')->insert($audit);
        $master = DbMasterArticle::create($masterData);
        DB::table('csv_historial_detalles')->insert([
            'historial_id' => $historialId, 'clave' => $claveReal, 'columna' => 'TODAS (CREACIÓN)',
            'valor_anterior' => 'NO EXISTÍA', 'valor_nuevo' => 'ALTA EN MAESTRO', 'sucursal' => 'maestro',
        ]);

        // Matriz de Homologación: presente y activo en todas las sucursales (igual que Crear Artículo)
        try {
            $matrizData = array_intersect_key($masterData, array_flip([
                'descripcion', 'unidad_medida', 'linea', 'clasificacion', 'area', 'mn_usd', 'precio_lista',
                'des_precio_venta', 'precio_venta', 'desc_precio_espec', 'precio_especial', 'desc_precio4', 'precio4',
                'desc_proveedor', 'precio_gerente', 'precio_tope', 'costo_venta', 'porcetaje_descuento', 'articulo_kit',
                'articulo_serie', 'margen_minimo', 'color', 'protocolo', 'idsat', 'id_impuesto_sat', 'iva', 'habilitado', 'fecha_alta',
            ]));
            $physicalCols = \App\Models\MatrizHomologacion::getPhysicalBranchColumns();
            foreach (array_keys($nombres) as $bCode) {
                $colName = \App\Models\MatrizHomologacion::resolveColumnName($bCode);
                if (in_array($colName, $physicalCols)) $matrizData[$colName] = 1;
            }
            \App\Models\MatrizHomologacion::updateOrCreate(['clave' => $claveReal], $matrizData);
        } catch (Throwable $e) {
            Log::warning("Carga masiva: no se pudo actualizar la matriz al dar de alta {$claveReal}: " . $e->getMessage());
        }

        return [
            'master'  => $master,
            'accion'  => $nuevo ? 'creado_nuevo' : ($activadas ? 'activado' : ($creadas ? 'creado_sucursales' : 'alta_maestro')),
            'creadas' => $creadas,
            'activadas' => $activadas,
            'fuente'  => $origen,
        ];
    }

    /** Valor "vacío" según el tipo de columna MySQL, para columnas NOT NULL sin default. */
    private function valorVacioColumna(string $tipo): string|int
    {
        $t = strtolower($tipo);
        if (preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint|decimal|numeric|float|double|real|bit|year)/', $t)) return 0;
        if (str_starts_with($t, 'datetime') || str_starts_with($t, 'timestamp')) return '1901-01-01 00:00:00';
        if (str_starts_with($t, 'date')) return '1901-01-01';
        if (str_starts_with($t, 'time')) return '00:00:00';
        return '';
    }

    /**
     * Paso 4: arma el renglón de sucursal (PascalCase) para una clave que no existe en ninguna sucursal,
     * con los mismos valores por defecto de Crear Artículo (storeManual) y los datos del CSV encima.
     * Valida lo mismo que Crear Artículo para los obligatorios. Devuelve [renglón, null] o [null, motivo].
     *
     * @param array $createData campos snake_case ya convertidos (booleanos, numéricos, MN/USD, truncados)
     */
    /**
     * Mensaje cuando la primera fila no trae la columna Clave.
     * Si no se reconoce ninguna columna, el archivo casi seguro no trae encabezado (empieza con datos).
     */
    private function mensajeSinClave(array $headerMap, array $headerOriginal): string
    {
        if (empty($headerMap)) {
            $primero = trim((string) ($headerOriginal[0] ?? ''));
            return 'El archivo no tiene encabezado: la primera fila debe ser el encabezado del machote '
                . '(Clave, Descripción, U.M., Línea, …) y tu archivo empieza directamente con datos'
                . ($primero !== '' ? ' ("' . mb_substr($primero, 0, 40) . '")' : '') . '. '
                . 'Descarga el machote, pega tus datos debajo del encabezado y vuelve a subirlo.';
        }
        $colsFound = array_filter(array_map('trim', $headerOriginal));
        return 'Falta la columna "Clave" en el encabezado del archivo. Usa el encabezado del machote. '
            . 'Columnas detectadas: ' . implode(', ', $colsFound);
    }

    private function renglonNuevoDesdeCsv(string $clave, array $createData): array
    {
        $faltan = [];
        foreach (self::CAMPOS_CREAR as $f) {
            if (!array_key_exists($f, $createData) || trim((string) $createData[$f]) === '') {
                $faltan[] = self::ETIQUETAS_CREAR[$f] ?? $f;
            }
        }
        if ($faltan) return [null, 'Para crearlo falta: ' . implode(', ', $faltan)];

        $errores = [];
        if (mb_strlen($clave) > 40 || !preg_match('/^[A-Za-z0-9Ññ\/.\-+*"_() ]+$/u', $clave)) {
            $errores[] = 'la clave tiene caracteres no permitidos o más de 40';
        }
        $um = strtoupper(trim((string) $createData['unidad_medida']));
        if (!preg_match('/^[A-Z0-9]{1,4}$/', $um)) $errores[] = "U.M. '{$um}' no válida (letras/números, máx. 4)";
        $linea = strtoupper(trim((string) $createData['linea']));
        if (!preg_match('/^[A-Z0-9]{1,4}$/', $linea)) $errores[] = "Línea '{$linea}' no válida (letras/números, máx. 4)";
        $area = $createData['area'];
        if (!is_numeric($area) || (float) $area != (int) $area || !in_array((int) $area, $this->areasValidas(), true)) {
            $errores[] = "Área '{$area}' no existe en el catálogo de áreas";
        }
        if ($errores) return [null, 'No se pudo crear: ' . implode('; ', $errores)];

        // Valores por defecto de Crear Artículo (evitan NOT NULL en las sucursales)
        $data = array_merge([
            'id_impuesto_sat' => '002', 'iva' => 16, 'id_tipo_factor' => 'Tasa', 'color' => 0,
            'protocolo' => 0, 'articulo_kit' => 0, 'articulo_serie' => 0, 'en_promocion' => 0, 'critico' => 0,
            'control_pedimentos' => 0, 'ubicacion' => 'GENERAL', 'idsat' => '01010101',
            'sustituto' => '0', 'sustituto1' => '0', 'sustituto2' => '0', 'peso' => 0, 'std_pack' => 1,
            'precio_lista' => 0, 'precio_venta' => 0, 'des_precio_venta' => 0, 'precio_especial' => 0,
            'desc_precio_espec' => 0, 'precio4' => 0, 'desc_precio4' => 0, 'desc_proveedor' => 0, 'precio_gerente' => 0,
            'precio_tope' => 0, 'costo_venta' => 0, 'porcetaje_descuento' => 0, 'margen_minimo' => 0,
        ], array_filter($createData, fn ($v) => $v !== null && $v !== ''));
        $data['unidad_medida'] = $um;
        $data['linea'] = $linea;
        $data['area'] = (int) $area;
        $data['habilitado'] = 1;
        $data['fecha_alta'] = now()->toDateString();

        // Lo propio de cada sucursal en cero (existencias, costos, mín/máx…), luego los datos del artículo
        $row = self::COLUMNAS_PROPIAS_SUCURSAL;
        foreach (\App\Support\ArticuloFieldMap::map() as $snake => $pascal) {
            if (array_key_exists($snake, $data)) $row[$pascal] = $data[$snake];
        }
        $row['Clave_Articulo'] = $clave;
        $row['Habilitado'] = 1;
        return [$row, null];
    }

    /** Cambio 2: motivos del resultado por fila de la carga masiva (código => texto). */
    public const MOTIVOS_CARGA = [
        'actualizado'          => 'Actualizado',
        'creado_sucursales'    => 'Agregado en sucursales y en el Catálogo Maestro',
        'alta_maestro'         => 'Agregado al Catálogo Maestro (ya estaba activo en todas las sucursales)',
        'activado'             => 'Activado (estaba inactivo) y agregado al Catálogo Maestro',
        'creado_nuevo'         => 'Creado desde cero en las sucursales y en el Catálogo Maestro',
        'faltan_datos'         => 'No se pudo crear (faltan o no son válidos datos obligatorios)',
        'error_sucursal'       => 'Actualizado con error en sucursal',
        'error'                => 'Error al procesar la fila',
        'inactivo_todas'       => 'Inactivo en todas las sucursales donde existe',
        'inactivo_algunas'     => 'Inactivo o sin alta en algunas sucursales',
        'activo_falta_maestro' => 'Activo en todas las sucursales, pero falta en el Catálogo Maestro',
        'no_existe'            => 'No existe en ninguna sucursal',
        'linea_no_aprobada'    => 'Línea no aprobada en Homologación (solo pasan las líneas "Sí se pasa")',
        'clave_invalida'       => 'Clave con espacios o caracteres especiales',
        'no_numerico'          => 'Valor no numérico',
        'sin_clave'            => 'Fila sin clave',
        'sin_datos'            => 'Sin columnas para actualizar',
        'no_procesado'         => 'No procesado',
    ];

    public static function cargaResultadosFile(int $historialId): string
    {
        return storage_path('app/cargas_masivas/' . $historialId . '_resultados.csv');
    }

    /**
     * Cambio 2: motivo de las claves que no están en el maestro, según si existen y si están
     * activas (Habilitado) en cada sucursal activa. El maestro solo trae artículos activos en
     * TODAS las sucursales (SyncDbMaster), por eso esa es la explicación habitual.
     */
    private function clasificarNoEncontradas(array &$resultados, array $indices, array $branchesMap): void
    {
        if (empty($indices)) return;

        $limpia = fn (string $c) => strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $c));

        $claves = [];
        foreach ($indices as $i) {
            $c = $limpia($resultados[$i]['clave']);
            if ($c !== '') $claves[$c] = true;
        }

        $info = [];          // clave => [codigo_sucursal => Habilitado]
        $sinRespuesta = [];
        foreach ($branchesMap as $code => $name) {
            try {
                $cn = $this->connectionManager->connect($code);
                foreach (array_chunk(array_keys($claves), 500) as $chunk) {
                    foreach ($cn->table('articulo')->whereIn('Clave_Articulo', $chunk)->get(['Clave_Articulo', 'Habilitado']) as $r) {
                        $info[strtoupper((string) $r->Clave_Articulo)][$code] = (int) $r->Habilitado;
                    }
                }
            } catch (Throwable $e) {
                $sinRespuesta[] = $name;
            }
        }

        $totalSucursales = count($branchesMap);
        foreach ($indices as $i) {
            $raw = (string) $resultados[$i]['clave'];
            $c = $limpia($raw);
            $rawNorm = strtoupper(str_replace(['-', ' '], '', trim($raw)));
            $en = $info[$c] ?? [];

            $activas = $inactivas = $faltan = [];
            foreach ($branchesMap as $code => $name) {
                if (!array_key_exists($code, $en)) $faltan[] = $name;
                elseif ($en[$code] === 1) $activas[] = $name;
                else $inactivas[] = $name;
            }

            if (empty($en)) {
                $motivo = 'no_existe';
                $detalle = 'No está en el Catálogo Maestro ni en ninguna sucursal';
            } elseif (count($activas) === $totalSucursales) {
                $motivo = 'activo_falta_maestro';
                $detalle = 'Sincronizar Homologación y luego Catálogo Maestro para que entre al maestro';
            } elseif (empty($activas)) {
                $motivo = 'inactivo_todas';
                $detalle = 'Inactivo en: ' . implode(', ', $inactivas) . ($faltan ? ' · No existe en: ' . implode(', ', $faltan) : '');
            } else {
                $motivo = 'inactivo_algunas';
                $detalle = ($inactivas ? 'Inactivo en: ' . implode(', ', $inactivas) : '')
                    . ($faltan ? ($inactivas ? ' · ' : '') . 'No existe en: ' . implode(', ', $faltan) : '')
                    . ' · Activo en: ' . implode(', ', $activas);
            }

            // Espacios invisibles (p.ej. el que deja Excel) u otros caracteres fuera de A-Z / 0-9
            if ($c !== $rawNorm) {
                $detalle = 'La clave trae espacios o caracteres especiales. ' . $detalle;
                $motivo = 'clave_invalida';
            }
            if ($sinRespuesta) {
                $detalle .= ' (sin respuesta de: ' . implode(', ', $sinRespuesta) . ')';
            }

            $resultados[$i]['motivo'] = $motivo;
            $resultados[$i]['detalle'] = $detalle;
        }
    }

    /** Cambio 2: marca claves repetidas en el archivo y cuenta filas por motivo. */
    private function completarResultadosCarga(array &$resultados): array
    {
        $porClave = [];
        foreach ($resultados as $i => $r) {
            $c = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $r['clave']));
            if ($c !== '') $porClave[$c][] = $i;
        }
        $repetidas = 0;
        foreach ($porClave as $idx) {
            if (count($idx) < 2) continue;
            $filas = implode(', ', array_map(fn ($i) => $resultados[$i]['fila'], $idx));
            foreach ($idx as $i) {
                $resultados[$i]['detalle'] = trim($resultados[$i]['detalle'] . ' · Clave repetida en el archivo (filas ' . $filas . ')', ' ·');
                $repetidas++;
            }
        }

        $motivos = [];
        foreach ($resultados as $r) {
            $motivos[$r['motivo']] = ($motivos[$r['motivo']] ?? 0) + 1;
        }
        return ['motivos' => $motivos, 'repetidas' => $repetidas, 'total' => count($resultados)];
    }

    private function guardarResultadosCarga(int $historialId, array $resultados): void
    {
        $file = self::cargaResultadosFile($historialId);
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }
        $tmp = $file . '.tmp';
        $h = fopen($tmp, 'w');
        fwrite($h, "\xEF\xBB\xBF");
        fputcsv($h, ['fila', 'clave', 'descripcion', 'motivo', 'detalle']);
        foreach ($resultados as $r) {
            fputcsv($h, $this->cleanUtf8([$r['fila'], $r['clave'], $r['descripcion'], $r['motivo'], $r['detalle']]));
        }
        fclose($h);
        @unlink($file);
        rename($tmp, $file);
    }

    private function leerResultadosCarga(int $historialId): ?array
    {
        $file = self::cargaResultadosFile($historialId);
        if (!is_file($file)) return null;
        $h = fopen($file, 'r');
        $head = fgetcsv($h);
        if ($head) $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $head[0]);
        $filas = [];
        while (($r = fgetcsv($h)) !== false) {
            if (count($r) < 5) continue;
            $filas[] = ['fila' => (int) $r[0], 'clave' => $r[1], 'descripcion' => $r[2], 'motivo' => $r[3], 'detalle' => $r[4]];
        }
        fclose($h);
        return $filas;
    }

    /** Cambio 2: resultados por fila de una subida (resumen + detalle) para la tabla de la pantalla. */
    public function historialResultados($id)
    {
        $filas = $this->leerResultadosCarga((int) $id);
        if ($filas === null) {
            return response()->json([
                'success' => false,
                'message' => 'Esta subida no tiene tabla de resultados (se hizo antes de esta función o el archivo ya no está).',
            ], 404);
        }
        $conteo = [];
        $repetidas = 0;
        foreach ($filas as $f) {
            $conteo[$f['motivo']] = ($conteo[$f['motivo']] ?? 0) + 1;
            if (str_contains($f['detalle'], 'Clave repetida en el archivo')) $repetidas++;
        }
        $resumen = [];
        foreach (self::MOTIVOS_CARGA as $codigo => $texto) {
            if (!empty($conteo[$codigo])) {
                $resumen[] = ['codigo' => $codigo, 'texto' => $texto, 'filas' => $conteo[$codigo]];
            }
        }
        return response()->json($this->cleanUtf8([
            'success'   => true,
            'historial_id' => (int) $id,
            'total'     => count($filas),
            'resumen'   => $resumen,
            'repetidas' => $repetidas,
            'motivos'   => self::MOTIVOS_CARGA,
            'filas'     => $filas,
        ]));
    }

    /** Cambio 2: descarga del resultado por fila (CSV para Excel). */
    public function descargarResultados($id)
    {
        $filas = $this->leerResultadosCarga((int) $id);
        if ($filas === null) {
            return redirect()->back()->with('error', 'Esta subida no tiene tabla de resultados.');
        }
        $motivos = self::MOTIVOS_CARGA;
        return response()->streamDownload(function () use ($filas, $motivos) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Fila', 'Clave', 'Descripción', 'Resultado', 'Detalle']);
            foreach ($filas as $f) {
                fputcsv($out, [$f['fila'], $f['clave'], $f['descripcion'], $motivos[$f['motivo']] ?? $f['motivo'], $f['detalle']]);
            }
            fclose($out);
        }, 'resultados_subida_' . (int) $id . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Columnas numéricas de la carga masiva (procesar y vista previa usan la misma lista).
     */
    private function columnasNumericasCsv(): array
    {
        return [
            'precio_lista', 'precio_venta', 'des_precio_venta', 'precio_especial',
            'desc_precio_espec', 'precio4', 'desc_precio4', 'desc_proveedor', 'precio_gerente', 'precio_tope', 'costo_venta',
            'porcetaje_descuento', 'margen_minimo', 'area', 'iva', 'peso',
            'inventario_maximo', 'inventario_minimo', 'punto_reorden', 'std_pack',
        ];
    }

    /**
     * AU-05: ¿cambió el valor? Si ambos son números se comparan como número
     * (35.7800 = 35.78); si no, como texto, igual que antes.
     */
    private function valorCambio($old, $new): bool
    {
        if (is_numeric($old) && is_numeric($new)) {
            return abs((float)$old - (float)$new) > 0.00001;
        }
        return (string)$old !== (string)$new;
    }

    /**
     * AU-01: interpreta un valor numérico del CSV.
     * Vacío → 0 (igual que antes). Acepta coma de miles solo con formato exacto
     * (1,234 / 1,234.50); "1,5" o "abc" se rechazan.
     *
     * @return array{0: bool, 1: mixed} [esNumero, valor]
     */
    private function parseNumeroCsv($val): array
    {
        $v = trim((string)$val);
        if ($v === '') return [true, 0];
        if (is_numeric($v)) return [true, $v];
        if (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $v)) return [true, str_replace(',', '', $v)];
        return [false, $v];
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
            if (!$handle) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo abrir el archivo CSV para lectura.'
                ], 422);
            }

            $rawHeaderLine = fgets($handle);
            if (!$rawHeaderLine) {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo subido está completamente vacío.'
                ], 422);
            }

            $delimiter = (str_contains($rawHeaderLine, ';')) ? ';' : ',';
            rewind($handle);
            $headerOriginal = fgetcsv($handle, 0, $delimiter);

            if (!$headerOriginal || empty($headerOriginal)) {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron leer los encabezados del archivo CSV.'
                ], 422);
            }

            // Sanitizar y limpiar codificación de los encabezados (eliminando BOM si existe)
            $headerOriginal = array_map([$this, 'cleanUtf8'], $headerOriginal);
            if (!empty($headerOriginal)) {
                $headerOriginal[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headerOriginal[0]);
            }

            $headerMap = [];
            foreach ($headerOriginal as $index => $h) {
                $normH = $normalize($h);
                if (isset($standardMapping[$normH])) {
                    $headerMap[$index] = $standardMapping[$normH];
                }
            }

            if (!in_array('clave', $headerMap)) {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => $this->mensajeSinClave($headerMap, $headerOriginal),
                ], 422);
            }

            // Columnas del CSV no reconocidas por el sistema
            $unrecognized = [];
            foreach ($headerOriginal as $index => $h) {
                if (!isset($headerMap[$index])) {
                    $display = trim(preg_replace('/[\x00-\x1F]/', '', (string)$h));
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

            // ── LECTURA RÁPIDA: CARGAR HASTA 500 FILAS PARA LA PREVIA Y CONTAR EL TOTAL DEL CSV ──
            $maxPreviewRows = 500;
            $previewRows = [];
            $previewClaves = [];
            $totalCsvRows = 0;

            // AU-01: columnas numéricas seleccionadas (mismas reglas que al procesar)
            $numericasSeleccionadas = [];
            foreach ($this->columnasNumericasCsv() as $numField) {
                foreach ($standardMapping as $uiNameNorm => $mappedField) {
                    if ($mappedField === $numField && in_array($uiNameNorm, $colsUiNorm, true)) {
                        $numericasSeleccionadas[] = $numField;
                        break;
                    }
                }
            }
            $headerLabel = [];
            foreach ($headerMap as $idx => $mappedField) {
                $headerLabel[$mappedField] = $headerOriginal[$idx];
            }
            $filasInvalidas = [];
            $clavesEstimacion = []; // Cambio 3
            // Paso 1: por clave (de TODO el archivo), Estatus del CSV y datos obligatorios que faltan para crearla
            $infoClaves = [];
            $estatusSeleccionado = in_array($normalize('Estatus'), $colsUiNorm, true);
            $fila = 1; // la fila 1 es el encabezado

            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                $fila++;
                // Sanitizar celdas a UTF-8 limpio
                $row = array_map([$this, 'cleanUtf8'], $row);

                // Verificar que no sea una fila vacía
                $nonEmpty = false;
                foreach ($row as $v) {
                    if (trim((string)$v) !== '') {
                        $nonEmpty = true;
                        break;
                    }
                }
                if (!$nonEmpty) continue;

                $totalCsvRows++;

                $csvItem = [];
                foreach ($row as $index => $value) {
                    if (isset($headerMap[$index])) {
                        $value = trim((string)$value);
                        // Misma regla que al procesar: celda vacía = sin cambio
                        if ($value === '' && $headerMap[$index] !== 'clave') continue;
                        $csvItem[$headerMap[$index]] = $value;
                    }
                }

                // AU-01: validar números en TODO el archivo (no solo las 500 filas visibles).
                // "1,234.50" se toma como 1234.50; "abc" se marca y esa fila se omitirá al procesar.
                $invalidos = [];
                foreach ($numericasSeleccionadas as $numField) {
                    if (!array_key_exists($numField, $csvItem)) continue;
                    [$esNumero, $numero] = $this->parseNumeroCsv($csvItem[$numField]);
                    if ($esNumero) {
                        if ($csvItem[$numField] !== '') $csvItem[$numField] = (string)$numero;
                    } else {
                        $invalidos[$numField] = true;
                        $filasInvalidas[] = "Fila {$fila} (" . ($csvItem['clave'] ?? '') . "): "
                            . ($headerLabel[$numField] ?? $numField) . " = '{$csvItem[$numField]}' no es un número";
                    }
                }
                if (!empty($invalidos)) $csvItem['__invalidos'] = $invalidos;

                // Cambio 3: claves de TODO el archivo para estimar cuántos artículos se procesarán
                if (empty($invalidos)) {
                    $claveEst = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($csvItem['clave'] ?? '')));
                    if ($claveEst !== '') {
                        $clavesEstimacion[] = $claveEst;
                        // Si la clave se repite, manda la última fila (igual que al procesar)
                        $infoClaves[$claveEst] = [
                            'estatus' => ($estatusSeleccionado && array_key_exists('habilitado', $csvItem))
                                ? $this->parseBooleanValue($csvItem['habilitado']) : null,
                            'faltan'  => array_values(array_diff(self::CAMPOS_CREAR, array_keys($csvItem))),
                            'area'    => $csvItem['area'] ?? null,   // paso 4: se valida contra el catálogo de áreas
                        ];
                    }
                }

                // Solo recopilamos los primeros 500 artículos para la previsualización
                if (count($previewRows) < $maxPreviewRows) {
                    $rawClave = $csvItem['clave'] ?? null;
                    if ($rawClave) {
                        // Normalizar clave eliminando guiones y espacios para que no se inserten guiones y cuadre con la BD
                        $cleanClave = strtoupper(str_replace(['-', ' '], '', trim((string)$rawClave)));
                        $csvItem['clave'] = $cleanClave;
                        $previewRows[] = $csvItem;
                        $previewClaves[] = $cleanClave;
                    }
                }
            }
            fclose($handle);

            if ($totalCsvRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo CSV no contiene registros o filas de artículos para procesar (solo contiene los encabezados o filas vacías).'
                ], 422);
            }

            if (empty($previewClaves)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron artículos con Clave válida en el archivo CSV. Asegúrese de que la columna "Clave" contenga datos.'
                ], 422);
            }

            // ── BÚSQUEDA INTELIGENTE DE ARTÍCULOS EN LA BASE MAESTRA (Exacta y Normalizada) ──
            $exactClaves = array_values(array_unique(array_filter($previewClaves)));
            $cleanClaves = array_values(array_unique(array_filter(array_map(function($c) {
                return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$c));
            }, $exactClaves))));

            $searchClaves = array_values(array_unique(array_merge($exactClaves, $cleanClaves)));

            $masterQuery = DbMasterArticle::whereIn('clave', $searchClaves);
            if (!empty($cleanClaves)) {
                $masterQuery->orWhereIn(DB::raw("REPLACE(REPLACE(REPLACE(clave, '-', ''), ' ', ''), '_', '')"), $cleanClaves);
            }
            $masterRecords = $masterQuery->get();

            // Mapeos rápidos O(1) para resolución inmediata
            $masterByExact = [];
            $masterByClean = [];
            foreach ($masterRecords as $m) {
                $masterByExact[$m->clave] = $m;
                $masterByExact[strtoupper(trim($m->clave))] = $m;
                $cleanM = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$m->clave));
                if (!isset($masterByClean[$cleanM])) {
                    $masterByClean[$cleanM] = $m;
                }
            }

            $resolveMaster = function($c) use (&$masterByExact, &$masterByClean) {
                if (!$c) return null;
                if (isset($masterByExact[$c])) return $masterByExact[$c];
                $u = strtoupper(trim($c));
                if (isset($masterByExact[$u])) return $masterByExact[$u];
                $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$c));
                if ($clean !== '' && isset($masterByClean[$clean])) return $masterByClean[$clean];
                return null;
            };

            // Paso 1: qué pasará con las claves que no están en el maestro (solo lectura en sucursales)
            $enMaestroSet = $this->clavesEnMaestro(array_keys($infoClaves));
            $clavesNoMaestro = array_values(array_filter(array_keys($infoClaves), fn ($k) => !isset($enMaestroSet[$k])));
            $accionesNoMaestro = $this->accionesNoMaestro($clavesNoMaestro, $infoClaves);
            $resumenNoMaestro = ['total' => count($accionesNoMaestro), 'por_accion' => [], 'activar' => []];
            foreach ($accionesNoMaestro as $kNm => $acc) {
                $resumenNoMaestro['por_accion'][$acc['accion']] = ($resumenNoMaestro['por_accion'][$acc['accion']] ?? 0) + 1;
                // Paso 2: lista para el aviso de artículos inactivos que se activarán
                if (!empty($acc['inactiva_en']) && count($resumenNoMaestro['activar']) < 200) {
                    $resumenNoMaestro['activar'][] = ['clave' => (string) $kNm, 'en' => implode(', ', $acc['inactiva_en'])];
                }
            }

            $diffs = [];
            $colsUiNorm = array_map($normalize, $columnsSelected);
            $changedCols = [];

            foreach ($previewRows as $csvItem) {
                $clave = $csvItem['clave'];
                $masterItem = $resolveMaster($clave);

                if (!$masterItem) {
                    // Nuevo artículo (no está en el maestro). Paso 1: qué se hará con él
                    $acc = $accionesNoMaestro[strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$clave))] ?? null;
                    $diffs[] = [
                        'clave'           => $clave,
                        'canonical_clave' => $clave,
                        'status'          => 'new',
                        'data'            => $csvItem,
                        'accion'          => $acc['accion'] ?? null,
                        'accion_tipo'     => $acc['tipo'] ?? null,
                        'accion_texto'    => $acc['texto'] ?? null,
                    ];
                    continue;
                }

                $canonicalClave = $masterItem->clave;

                $rowDiff = [];
                $hasDifference = false;

                // AU-01: fila con números inválidos → se muestra marcada y se omitirá al procesar
                $invalidosFila = $csvItem['__invalidos'] ?? [];
                if (!empty($invalidosFila)) {
                    foreach ($invalidosFila as $field => $_) {
                        $rowDiff[$field] = [
                            'old'     => $masterItem->{$field},
                            'new'     => $csvItem[$field],
                            'invalid' => true,
                        ];
                    }
                    $diffs[] = [
                        'clave'           => $canonicalClave,
                        'canonical_clave' => $canonicalClave,
                        'description'     => $masterItem->descripcion,
                        'status'          => 'update',
                        'invalid'         => true,
                        'diff'            => $rowDiff,
                        'full_new'        => $csvItem,
                        'master_data'     => $masterItem->toArray()
                    ];
                    continue;
                }

                foreach ($csvItem as $field => $csvVal) {
                    if ($field === 'clave' || $field === '__invalidos') continue;

                    // Verificar si el campo fue seleccionado en la UI
                    $foundInUi = false;
                    foreach ($standardMapping as $uiNameNorm => $mappedField) {
                        if ($mappedField === $field && in_array($uiNameNorm, $colsUiNorm)) {
                            $foundInUi = true;
                            break;
                        }
                    }
                    if (!$foundInUi) continue;

                    $booleanFields = ['habilitado', 'articulo_kit', 'articulo_serie', 'en_promocion', 'critico', 'control_pedimentos', 'protocolo'];
                    $masterVal = $masterItem->{$field};

                    if (in_array($field, $booleanFields, true)) {
                        $csvVal = $this->parseBooleanValue($csvVal);
                        $masterVal = $this->parseBooleanValue($masterVal);
                    } elseif ($field === 'mn_usd') {
                        $csvVal = in_array(strtoupper(trim((string)$csvVal)), ['1', 'U', 'USD', 'DLLS'], true) ? 'U' : 'M';
                        $masterVal = in_array(strtoupper(trim((string)$masterVal)), ['1', 'U', 'USD', 'DLLS'], true) ? 'U' : 'M';
                    }
                    
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
                $pVenta = isset($csvItem['precio_venta']) ? (float)$csvItem['precio_venta'] : (float)($masterItem->precio_venta ?? 0);
                $d4     = isset($csvItem['desc_precio4']) ? (float)$csvItem['desc_precio4'] : (float)($masterItem->desc_precio4 ?? 0);
                $dEsp   = isset($csvItem['desc_precio_espec']) ? (float)$csvItem['desc_precio_espec'] : (float)($masterItem->desc_precio_espec ?? 0);
                $dProv  = isset($csvItem['desc_proveedor']) ? (float)$csvItem['desc_proveedor'] : (float)($masterItem->desc_proveedor ?? 0);
                $pDesc  = isset($csvItem['porcetaje_descuento']) ? (float)$csvItem['porcetaje_descuento'] : (float)($masterItem->porcetaje_descuento ?? 0);

                // Calcular Desc. P. Venta con la regla del ERP (AU-04)
                $calcDescVenta = \App\Support\ArticuloValidacion::descPrecioVenta($pLista, $pVenta);

                $formulaFields = [
                    'precio4'          => ['label' => 'Precio 4',       'triggers' => ['P. Lista', 'Desc. Precio 4'], 'new' => round($pLista * (100 - $d4) / 100, 2),    'old' => round((float)($masterItem->precio4 ?? 0), 2)],
                    'precio_especial'  => ['label' => 'P. Especial',     'triggers' => ['P. Lista', 'Desc. P. Espec'],  'new' => round($pLista * (100 - $dEsp) / 100, 2),  'old' => round((float)($masterItem->precio_especial ?? 0), 2)],
                    'precio_gerente'   => ['label' => 'Precio Gerente',  'triggers' => ['P. Lista', 'Desc. Proveedor'], 'new' => round($pLista * (100 - $dProv) / 100, 2), 'old' => round((float)($masterItem->precio_gerente ?? 0), 2)],
                    'precio_tope'      => ['label' => 'Precio Tope',     'triggers' => ['P. Lista', '% Descuento'],     'new' => round($pLista * (100 - $pDesc) / 100, 2), 'old' => round((float)($masterItem->precio_tope ?? 0), 2)],
                    'des_precio_venta' => ['label' => 'Desc. P. Venta',  'triggers' => ['P. Lista', 'P. Venta'],        'new' => $calcDescVenta,                            'old' => round((float)($masterItem->des_precio_venta ?? 0), 2)],
                ];

                foreach ($formulaFields as $fKey => $fInfo) {
                    // Evaluar fórmulas si su columna o alguno de sus detonadores está seleccionado en la UI
                    $triggers = $fInfo['triggers'] ?? ['P. Lista'];
                    $isColSelected = in_array($fInfo['label'], $columnsSelected) || !empty(array_intersect($triggers, $columnsSelected));
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
                        'clave'           => $canonicalClave,
                        'canonical_clave' => $canonicalClave,
                        'description'     => $masterItem->descripcion,
                        'status'          => 'update',
                        'diff'            => $rowDiff,
                        'full_new'        => $csvItem,
                        'master_data'     => $masterItem->toArray()
                    ];
                }
            }

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

            $request->session()->put('preview_completed_hash', md5_file($file->getRealPath()));

            $responsePayload = [
                'success'      => true,
                'diffs'        => $diffs,
                'filas_invalidas' => $filasInvalidas, // AU-01: aviso; esas filas se omitirán al procesar
                'count'        => count($diffs),
                'preview_rows' => count($previewRows),
                'total_rows'   => $totalCsvRows,
                'is_truncated' => ($totalCsvRows > $maxPreviewRows),
                'max_preview'  => $maxPreviewRows,
                'changed_cols' => array_keys($changedCols),
                'column_map'   => $columnMap,
                'unrecognized' => $unrecognized,
                // Cambio 3: estimación de tiempo para avisar de archivos grandes
                // Paso 3: las que se crearán donde faltan o se agregarán al maestro también se procesan
                'en_maestro'   => $this->contarEnMaestro($clavesEstimacion)
                    + ($resumenNoMaestro['por_accion']['crear_faltantes'] ?? 0)
                    + ($resumenNoMaestro['por_accion']['alta_maestro'] ?? 0)
                    + ($resumenNoMaestro['por_accion']['activar'] ?? 0)
                    + ($resumenNoMaestro['por_accion']['crear_activar'] ?? 0)
                    + ($resumenNoMaestro['por_accion']['crear_nuevo'] ?? 0),
                'segundos_por_articulo' => self::SEGUNDOS_POR_ARTICULO,
                // Paso 1: resumen de TODO el archivo de las claves que no están en el maestro
                'no_maestro'   => $resumenNoMaestro,
            ];

            return response()->json($this->cleanUtf8($responsePayload));

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $this->cleanUtf8($e->getMessage())
            ], 500);
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
                // Paso 3: los artículos creados por la carga no se borran al revertir (solo se regresan valores)
                if (str_starts_with((string)$log->columna, 'TODAS (')) continue;
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
    /**
     * ART-07: áreas permitidas según la tabla `areas` de las sucursales (hoy 0 a 5 en las 4).
     * Si no se puede leer ninguna sucursal se usa ese mismo rango.
     */
    private function areasValidas(): array
    {
        return \App\Support\ArticuloValidacion::areasValidas($this->connectionManager);
    }

    public function storeManual(\Illuminate\Http\Request $request)
    {
        // K04: espacios en medio son formato real de clave; solo se unen los dobles/múltiples
        // antes de validar, para que 'unique' compare la clave ya normalizada.
        if (is_string($request->input('clave'))) {
            $request->merge(['clave' => preg_replace('/ {2,}/', ' ', $request->input('clave'))]);
        }

        // ART-07: mayúsculas en línea/unidad, IDSAT sin espacios (incl. U+00A0), sustitutos sin espacios
        \App\Support\ArticuloValidacion::normalizarCatalogo($request);

        // ART-03: las sucursales son latin1; ver App\Rules\Latin1
        $latin1 = new \App\Rules\Latin1();

        // ART-07: el sustituto debe ser una clave que exista en el maestro y no la misma del artículo
        $sustitutoExiste = \App\Support\ArticuloValidacion::sustitutoExiste((string) $request->input('clave'));

        $data = $request->validate([
            // ART-09: caracteres que usan las claves reales de las sucursales; bloquea < > | ? # & , ; \ ' ` e invisibles
            'clave'               => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9Ññ\/.\-+*"_() ]+$/u', 'unique:db_master.Articulos,clave'],
            'descripcion'         => ['required', 'string', 'max:200', $latin1],
            'unidad_medida'       => ['required', 'string', 'max:4', 'regex:/^[A-Z0-9]+$/'],
            'linea'               => ['required', 'string', 'max:4', 'regex:/^[A-Z0-9]+$/'],
            'clasificacion'       => ['required', 'string', 'max:6', $latin1],
            'area'                => ['required', 'integer', \Illuminate\Validation\Rule::in($this->areasValidas())],
            'mn_usd'              => ['required', 'string', 'in:M,U,m,u,0,1'],
            'precio_lista'        => 'nullable|numeric|min:0',
            'porcentaje_pv'       => 'nullable|numeric|between:0,100',
            'precio_venta'        => 'nullable|numeric|min:0',
            'des_precio_venta'    => 'nullable|numeric',
            'precio_especial'     => 'nullable|numeric|min:0',
            'desc_precio_espec'   => 'nullable|numeric|between:0,100',
            'precio4'             => 'nullable|numeric|min:0',
            'desc_precio4'        => 'nullable|numeric|between:0,100',
            'desc_proveedor'      => 'nullable|numeric|between:0,100',
            'precio_gerente'      => 'nullable|numeric|min:0',
            'precio_tope'         => 'nullable|numeric|min:0',
            'costo_venta'         => 'nullable|numeric',
            'porcetaje_descuento' => 'nullable|numeric|between:0,100',
            'articulo_kit'        => 'nullable|boolean',
            'articulo_serie'      => 'nullable|boolean',
            'margen_minimo'       => 'nullable|numeric',
            'color'               => 'nullable|integer|between:0,9',
            'color_branch'        => 'nullable|array',
            'color_branch.*'      => 'nullable|integer|between:0,9',
            'protocolo'           => 'nullable|boolean',
            'idsat'               => ['nullable', 'string', 'regex:/^[0-9]{8}$/'],
            'id_impuesto_sat'     => 'nullable|string|max:3',
            'iva'                 => ['nullable', 'numeric', 'in:0,8,16'],
            'precio_minimo'       => 'nullable|numeric|min:0',
            'desc_precio_minimo'  => 'nullable|numeric|between:0,100',
            'sustituto'           => ['nullable', 'string', 'max:40', $latin1, $sustitutoExiste],
            'sustituto1'          => ['nullable', 'string', 'max:40', $latin1, $sustitutoExiste],
            'sustituto2'          => ['nullable', 'string', 'max:40', $latin1, $sustitutoExiste],
            'costo_promedio'      => 'nullable|numeric',
            'costo_promedio_ant'  => 'nullable|numeric',
            'costo_ult_compra'    => 'nullable|numeric',
            'fecha_ult_compra'    => 'nullable|date',
            'costo_compra_ant'    => 'nullable|numeric',
            'fecha_compra_ant'    => 'nullable|date',
            'en_promocion'        => 'nullable|boolean',
            'critico'             => 'nullable|boolean',
            'control_pedimentos'  => 'nullable|boolean',
            'ubicacion'           => ['nullable', 'string', 'max:10', $latin1],
            'peso'                => 'nullable|numeric|min:0',
            'std_pack'            => 'nullable|numeric|gt:0',
            'inventario_maximo'   => 'nullable|numeric',
            'inventario_minimo'   => 'nullable|numeric',
            'punto_reorden'       => 'nullable|numeric',
            'habilitado'          => 'required|boolean',
        ], \App\Support\ArticuloValidacion::mensajes(), \App\Support\ArticuloValidacion::atributos());

        // SIEMPRE se recalculan las fórmulas de precios a 2 decimales a partir de las bases
        $pLista = (float)($data['precio_lista'] ?? 0);
        $d4     = (float)($data['desc_precio4'] ?? 0);
        $dEsp   = (float)($data['desc_precio_espec'] ?? 0);
        $pPV    = isset($data['porcentaje_pv']) && is_numeric($data['porcentaje_pv']) ? (float)$data['porcentaje_pv'] : 5.27;
        $dProv  = (float)($data['desc_proveedor'] ?? 0);
        $pDesc  = (float)($data['porcetaje_descuento'] ?? 0);

        $calcPrecio4         = round($pLista * (100 - $d4) / 100, 2);
        $calcPrecioEsp       = round($pLista * (100 - $dEsp) / 100, 2);
        $calcPrecioGerente   = round($pLista * (100 - $dProv) / 100, 2);
        $calcPrecioTope      = round($pLista * (100 - $pDesc) / 100, 2);
        $calcPrecioVenta     = round($calcPrecioEsp * (1 + $pPV / 100), 2);
        $calcDescVenta       = \App\Support\ArticuloValidacion::descPrecioVenta($pLista, $calcPrecioVenta); // AU-04

        // Validación de campos calculados que se envíen fuera de pantalla (SRV)
        $erroresCalculados = [];
        if ($request->has('precio_venta') && $request->input('precio_venta') !== null && $request->input('precio_venta') !== '') {
            $reqPrecioVenta = (float)$request->input('precio_venta');
            if (abs($reqPrecioVenta - $calcPrecioVenta) > 0.05) {
                $erroresCalculados['precio_venta'] = 'El precio de venta (' . $reqPrecioVenta . ') no coincide con el cálculo matemático (' . $calcPrecioVenta . ').';
            }
        }

        if ($request->has('des_precio_venta') && $request->input('des_precio_venta') !== null && $request->input('des_precio_venta') !== '') {
            $reqDescVenta = (float)$request->input('des_precio_venta');
            if (abs($reqDescVenta - $calcDescVenta) > 0.05) {
                $erroresCalculados['des_precio_venta'] = 'El descuento de precio de venta (' . $reqDescVenta . '%) no coincide con el cálculo matemático (' . $calcDescVenta . '%).';
            }
        }

        if (!empty($erroresCalculados)) {
            throw \Illuminate\Validation\ValidationException::withMessages($erroresCalculados);
        }

        // ART-07: no hay catálogo de líneas ni unidades; se comparan contra las que ya usan
        // los artículos del maestro. Si es nueva se pide confirmar (se crean líneas reales nuevas).
        if (!$request->boolean('confirmar_catalogo')) {
            $nuevos = \App\Support\ArticuloValidacion::catalogoNuevo($data['linea'], $data['unidad_medida']);
            if (!empty($nuevos)) {
                return back()->withInput()->with('confirmar_catalogo', $nuevos);
            }
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Automatización de campos fijos y valores por defecto para sucursales (evitar NOT NULL SQL errors)
            $data['fecha_alta']      = now()->toDateString();
            $data['id_impuesto_sat'] = !empty($data['id_impuesto_sat']) ? $data['id_impuesto_sat'] : '002';
            $data['iva']             = isset($data['iva']) && $data['iva'] !== '' && $data['iva'] !== null ? $data['iva'] : 16;
            $data['id_tipo_factor']  = 'Tasa';
            $data['area']            = isset($data['area']) && $data['area'] !== '' && $data['area'] !== null ? (int)$data['area'] : 1;
            $isUsd                   = in_array(strtoupper(trim((string)($data['mn_usd'] ?? ''))), ['U', '1', 'USD'], true);
            $data['mn_usd']          = $isUsd ? 'U' : 'M';
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

            // Asignar valores calculados con fórmulas oficiales
            $data['precio4']          = $calcPrecio4;
            $data['precio_especial']  = $calcPrecioEsp;
            $data['precio_gerente']   = $calcPrecioGerente;
            $data['precio_tope']      = $calcPrecioTope;
            $data['precio_venta']     = $calcPrecioVenta;
            $data['des_precio_venta'] = $calcDescVenta;
            unset($data['porcentaje_pv']);

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
                    'mn_usd'              => $data['mn_usd'] ?? 'M',
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
