<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

use App\Models\MatrizHomologacion;
use App\Models\DbMasterArticle;
use App\Models\DbMasterSyncHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DBMasterController extends Controller
{
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

        $error = null;
        $stats = ['universo' => 0];

        try {
            $branches = $this->getDynamicBranches();
            // Ahora consultamos la tabla INDEPENDIENTE
            $query = DbMasterArticle::query();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('clave', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy('clave');

            $paginator = $query->paginate($perPage)->withQueryString();

            // Formateo para la vista
            $paginator->getCollection()->transform(function ($item) use ($branches) {
                // Convertir modelo a stdClass con TODOS los campos
                $out = (object) $item->toArray();

                // Compatibilidad con aliases previos en la vista
                $out->Codigo_Deasa      = $item->clave;
                $out->Descripcion_Deasa = $item->descripcion;
                
                // En esta tabla todos est�n al 100% de cobertura
                foreach ($branches as $info) {
                    $out->{$info['col']} = 'ACTIVO';
                }
                return $out;
            });

            $articles = $paginator;
            $stats['universo'] = $paginator->total();

            // Obtener última sincronización
            $lastSync = DbMasterSyncHistory::orderBy('created_at', 'DESC')->first();
            $stats['last_sync'] = $lastSync ? $lastSync->created_at->format('d/m/Y H:i') : 'Nunca';

        } catch (\Throwable $e) {
            $error    = 'Error consultando la base de datos maestra: ' . $e->getMessage();
            $articles = new LengthAwarePaginator([], 0, 50);
        }

        return view('db_master.index', [
            'articles' => $articles,
            'error'    => $error,
            'search'   => $search,
            'stats'    => $stats,
            'per_page' => $perPage,
            'branches' => $branches,
        ]);
    }

    /**
     * Sincroniza los artículos con cobertura total desde la matriz de homologación
     */
    public function sync()
    {
        try {
            $branches = $this->getDynamicBranches();
            // 1. Obtener los artículos de la matriz original con 100% cobertura
            $query = MatrizHomologacion::query();
            foreach ($branches as $branch) {
                $query->where($branch['col'], 1);
            }

            $sourceArticles = $query->get([
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
            ]);

            // 2. Limpiar la tabla de destino en la base db_master
            DbMasterArticle::truncate();

            // 3. Insertar los nuevos artículos (por chunks para no saturar memoria si es gigante)
            $chunks = $sourceArticles->toArray();
            foreach (array_chunk($chunks, 500) as $chunk) {
                // Agregar timestamps manuales si el toArray no los puso o si queremos control
                $now = now();
                foreach($chunk as &$c) {
                    $c['created_at'] = $now;
                    $c['updated_at'] = $now;
                }
                DbMasterArticle::insert($chunk);
            }

            // 4. Registrar en historial
            DbMasterSyncHistory::create([
                'total_articulos' => count($chunks)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Base de Datos Maestra actualizada correctamente.',
                'total' => count($chunks)
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al sincronizar: ' . $e->getMessage()
            ], 500);
        }
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
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exporta la base maestra a XLS con tabla HTML
     */
    public function export(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $filename = 'DB_Master_Articulos_' . now()->format('Y-m-d_His') . '.xls';

        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $out = fopen('php://output', 'w');

            // HTML Header para Excel
            fwrite($out, '<html xmlns:x="urn:schemas-microsoft-com:office:excel">');
            fwrite($out, '<head><meta charset="utf-8"></head><body>');
            fwrite($out, '<table border="1" style="font-family: Arial, sans-serif; font-size: 11px;">');
            
            // Mapeo exacto solicitado por el usuario (PascalCase para compatibilidad)
            $exportMap = [
                'Clave_Articulo'      => 'clave',
                'Descripcion'         => 'descripcion',
                'Unidad_Medida'       => 'unidad_medida',
                'Linea'               => 'linea',
                'Clasificacion'       => 'clasificacion',
                'MN_USD'              => 'mn_usd',
                'Precio_Lista'        => 'precio_lista',
                'Precio_Venta'        => 'precio_venta',
                'Desc_Precio_Venta'   => 'des_precio_venta',
                'Precio_Especial'     => 'precio_especial',
                'Desc_Precio_Espec'   => 'desc_precio_espec',
                'Precio4'             => 'precio4',
                'Desc_Precio4'        => 'desc_precio4',
                'CostoVenta'          => 'costo_venta',
                'PorcentajeDescuento' => 'porcetaje_descuento',
                'Articulo_Kit'        => 'articulo_kit',
                'Articulo_Serie'      => 'articulo_serie',
                'Margen_Minimo'       => 'margen_minimo',
                'Color'               => 'color',
                'Protocolo'           => 'protocolo',
                'IDSAT'               => 'idsat',
                'IDImpuestoSAT'       => 'id_impuesto_sat',
                'Area'                => 'area',
                'IVA'                 => 'iva',
                'Ubicacion'           => 'ubicacion',
                'Sustituto'           => 'sustituto',
                'Fecha_Alta'          => 'fecha_alta',
                'Habilitado'          => 'habilitado',
            ];

            fwrite($out, '<thead><tr>');
            foreach (array_keys($exportMap) as $header) {
                fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">' . $header . '</th>');
            }
            fwrite($out, '</tr></thead><tbody>');

            $query = DbMasterArticle::query()->orderBy('clave', 'asc');

            $query->chunkById(500, function ($rows) use ($out, $exportMap) {
                foreach ($rows as $item) {
                    fwrite($out, '<tr>');
                    foreach ($exportMap as $dbField) {
                        $val = $item->{$dbField};
                        fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)($val ?? '')) . '</td>');
                    }
                    fwrite($out, '</tr>');
                }
            }, 'id');

            fwrite($out, '</tbody></table></body></html>');
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Actualiza un artículo manualmente y replica cambios en sucursales
     */
    public function updateManual(Request $request, $id)
    {
        $item = DbMasterArticle::findOrFail($id);
        
        $data = $request->validate([
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
            'costo_venta'         => 'nullable|numeric',
            'porcetaje_descuento' => 'nullable|numeric',
            'articulo_kit'        => 'nullable|boolean',
            'articulo_serie'      => 'nullable|boolean',
            'margen_minimo'       => 'nullable|numeric',
            'color'               => 'nullable|boolean',
            'protocolo'           => 'nullable|boolean',
            'idsat'               => 'nullable|string|max:25',
            'id_impuesto_sat'     => 'nullable|string|max:3',
            'iva'                 => 'nullable|numeric',
            'habilitado'          => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            // 1. Detectar cambios para auditoría
            $auditEntries = [];
            foreach ($data as $field => $newVal) {
                $oldVal = $item->{$field};
                // Comparación flexible para decimales/strings
                if ((string)$oldVal !== (string)$newVal) {
                    $auditEntries[] = [
                        'clave'          => $item->clave,
                        'columna'        => strtoupper($field),
                        'valor_anterior' => (string)$oldVal,
                        'valor_nuevo'    => (string)$newVal,
                        'sucursal'       => 'MAESTRO'
                    ];
                }
            }

            if (empty($auditEntries)) {
                return response()->json(['status' => 'info', 'message' => 'No se detectaron cambios.']);
            }

            // 2. Log de historial centralizado
            $historialId = DB::table('csv_historial')->insertGetId([
                'archivo_nombre'      => 'EDICIÓN MANUAL',
                'archivo_path'        => null,
                'articulos_afectados' => 1,
                'sucursales_json'     => json_encode(['TODAS']),
                'fecha'               => now()
            ]);

            foreach ($auditEntries as &$entry) {
                $entry['historial_id'] = $historialId;
                $entry['sucursal'] = 'MAESTRO';
            }
            DB::table('csv_historial_detalles')->insert($auditEntries);

            // 3. Actualizar Maestro
            $item->update($data);

            // 4. Replicar a Sucursales (PascalCase Mapping)
            $branches = \App\Models\Branch::active()->get();
            $errors = [];
            
            $branchFieldMap = [
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
            ];

            $updateDataBranch = [];
            foreach ($data as $field => $val) {
                if (isset($branchFieldMap[$field])) {
                    $updateDataBranch[$branchFieldMap[$field]] = $val;
                }
            }

            $connectionManager = app(\App\Services\BranchConnectionManager::class);
            foreach ($branches as $branch) {
                try {
                    $conn = $connectionManager->connect($branch->code);
                    $conn->table('articulo')->where('Clave_Articulo', $item->clave)->update($updateDataBranch);
                } catch (\Throwable $e) {
                    $errors[] = "Error en {$branch->name}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Artículo actualizado y replicado correctamente.',
                'errors'  => $errors
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
