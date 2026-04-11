<?php

namespace App\Http\Controllers;

use App\Models\MatrizHomologacion;
use App\Console\Commands\SyncMatrizHomologacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

class HomologacionController extends Controller
{
    /**
     * Obtiene el listado de sucursales activas y sus columnas correspondientes en la matriz.
     */
    private function getDynamicBranches(): array
    {
        $activeBranches = \App\Models\Branch::query()->active()->orderBy('name')->get();
        $physicalCols = MatrizHomologacion::getPhysicalBranchColumns();
        
        $branches = [];
        foreach ($activeBranches as $branch) {
            $colName = MatrizHomologacion::resolveColumnName($branch->code);
            // Solo incluimos la sucursal si tiene columna física en la matriz para evitar errores SQL
            if (in_array($colName, $physicalCols)) {
                $branches[strtoupper($branch->name)] = [
                    'conn' => $branch->code,
                    'col'  => $colName
                ];
            }
        }

        return $branches;
    }

    public function index(Request $request): View
    {
        $search      = trim((string) $request->string('q'));
        $filterCol   = $request->string('filtro')->toString();
        $filterVal   = $request->string('estado')->toString();
        $cobertura   = $request->string('cobertura')->toString();   // preset: todas|ninguna|incompleta|solo_una
        $tienEn      = array_filter((array) $request->input('tiene_en', []));  // cols donde DEBE estar activo
        $faltaEn     = array_filter((array) $request->input('falta_en', []));  // cols donde debe FALTAR (null)
        $error       = null;
        $stats       = [];

        try {
            $branches = $this->getDynamicBranches();
            $allCols  = array_column(array_values($branches), 'col');

            $query = MatrizHomologacion::query();

            // ── Búsqueda rápida ──────────────────────────────────────────
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('clave', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion', 'LIKE', "%{$search}%");
                });
            }

            // ── Filtro clásico: sucursal + estado ────────────────────────
            if ($filterCol && $filterVal) {
                if ($filterVal === 'ACTIVO') {
                    $query->where($filterCol, 1);
                } elseif ($filterVal === 'INACTIVO') {
                    $query->where($filterCol, 0);
                } elseif ($filterVal === 'FALTA') {
                    $query->whereNull($filterCol);
                }
            }

            // ── Filtro de cobertura: presets ─────────────────────────────
            $branchColsForSum = array_map(fn($c) => "COALESCE(`{$c}`, 0)", $allCols);
            $sumExpr = implode(' + ', $branchColsForSum);
            $total   = count($allCols);

            if ($cobertura === 'todas') {
                // Activo en TODAS las sucursales
                foreach ($allCols as $col) {
                    $query->where($col, 1);
                }
            } elseif ($cobertura === 'ninguna') {
                // No existe en ninguna (todas son NULL)
                foreach ($allCols as $col) {
                    $query->whereNull($col);
                }
            } elseif ($cobertura === 'incompleta') {
                // Existe en AL MENOS UNA pero no en todas
                $query->whereRaw("({$sumExpr}) > 0");
                foreach ($allCols as $col) {
                    // NOT (todas activas)
                }
                // Tiene al menos una activa (>0) y falta al menos una (< total)
                $query->whereRaw("({$sumExpr}) < {$total}");
            } elseif ($cobertura === 'solo_una') {
                // Activo exactamente en 1 sucursal
                $query->whereRaw("({$sumExpr}) = 1");
            } elseif ($cobertura === 'todas_menos_una') {
                // Activo en todas menos exactamente una
                $query->whereRaw("({$sumExpr}) = " . ($total - 1));
            }

            // ── Filtro exacto por número de sucursales ───────────────────
            if ($request->filled('exact')) {
                $exactCount = (int) $request->input('exact');
                $query->whereRaw("({$sumExpr}) = {$exactCount}");
            }

            // ── Filtro cruzado: DEBE estar en estas sucursales ───────────
            foreach ($tienEn as $col) {
                if (in_array($col, $allCols, true)) {
                    $query->where($col, 1);
                }
            }

            // ── Filtro cruzado: NO debe estar en estas sucursales ────────
            foreach ($faltaEn as $col) {
                if (in_array($col, $allCols, true)) {
                    $query->whereNull($col);
                }
            }

            // ── Ordenamiento: por cobertura DESC ─────────────────────────
            // Paginación dinámica
            $perPage = (int) $request->input('per_page', 50);
            if (!in_array($perPage, [50, 100, 250, 500])) $perPage = 50;

            $paginator = $query
                ->orderByRaw("({$sumExpr}) DESC")
                ->orderBy('clave')
                ->paginate($perPage)
                ->withQueryString();

            $resultTotal = $query->toBase()->getCountForPagination();

            // ── Transformación al formato que espera la vista ─────────────
            $paginator->getCollection()->transform(function ($item) use ($branches) {
                $out = new \stdClass();
                $out->Codigo_Deasa      = $item->clave;
                $out->Descripcion_Deasa = $item->descripcion;
                foreach ($branches as $b) {
                    $raw = $item->getRawOriginal($b['col']);
                    if ($raw === 1 || $raw === '1') {
                        $out->{$b['col']} = 'ACTIVO';
                    } elseif ($raw === 0 || $raw === '0') {
                        $out->{$b['col']} = 'INACTIVO';
                    } else {
                        $out->{$b['col']} = 'FALTA';
                    }
                }
                return $out;
            });

            $articles = $paginator;

            $totalCount = MatrizHomologacion::count();
            if ($totalCount === 0) {
                $error = 'La matriz local está vacía. ¡Dale click en "Sincronizar" arriba a la derecha para llenarla por primera vez!';
            } else {
                $stats['universo'] = $totalCount;
            }

        } catch (\Throwable $e) {
            $error    = 'Error consultando la matriz (¿Problema de conexión o falta migrar?): ' . $e->getMessage();
            $articles = new LengthAwarePaginator([], 0, 50);
        }

        return view('homologacion.index', [
            'articles'   => $articles ?? new LengthAwarePaginator([], 0, 50),
            'error'      => $error,
            'search'     => $search,
            'filterCol'  => $filterCol,
            'filterVal'  => $filterVal,
            'cobertura'  => $cobertura,
            'tienEn'     => array_values($tienEn),
            'faltaEn'    => array_values($faltaEn),
            'branches'   => $branches,
            'stats'      => $stats,
            'per_page'   => $perPage ?? 50,
        ]);
    }


    /**
     * Motor de Sincronizaci\u00f3n \u2014 lanza en proceso de FONDO (no bloquea otros usuarios)
     */
    public function sync()
    {
        $statusFile = SyncMatrizHomologacion::statusFile();

        // Si ya hay una sincronizaci\u00f3n corriendo (< 10 min), rechazar
        if (file_exists($statusFile)) {
            $prev = json_decode(file_get_contents($statusFile), true);
            if (($prev['status'] ?? '') === 'running' && (time() - (int)($prev['updated_at'] ?? 0)) < 600) {
                return response()->json(['status' => 'already_running', 'message' => 'Ya hay una sincronizaci\u00f3n en progreso.']);
            }
        }

        // Escribir estado inicial
        file_put_contents($statusFile, json_encode([
            'status'     => 'running',
            'message'    => 'Iniciando sincronización...',
            'step'       => 0,
            'total'      => count($this->getDynamicBranches()),
            'updated_at' => time(),
        ], JSON_UNESCAPED_UNICODE));

        // \u2500\u2500 Lanzar proceso de fondo en Windows/XAMPP \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
        // strat /B = proceso sin ventana, sin esperar que termine.
        // Apache libera el thread inmediatamente => otros usuarios siguen normales.
        $php     = PHP_BINARY;
        $artisan = base_path('artisan');
        $log     = storage_path('logs') . DIRECTORY_SEPARATOR . 'sync_bg.log';

        $cmd = 'start "" /B "' . $php . '" "' . $artisan . '" unidata:sync-matriz >> "' . $log . '" 2>&1';
        pclose(popen($cmd, 'r'));

        return response()->json(['status' => 'started']);
    }

    /**
     * Polling: retorna JSON con el estado actual de la sincronizaci\u00f3n
     */
    public function syncStatus()
    {
        $statusFile = SyncMatrizHomologacion::statusFile();
        $totalBranches = count($this->getDynamicBranches());

        if (!file_exists($statusFile)) {
            return response()->json(['status' => 'idle', 'message' => 'Sin sincronización reciente.', 'step' => 0, 'total' => $totalBranches]);
        }

        return response()->json(
            json_decode(file_get_contents($statusFile), true) ?? []
        );
    }

    /**
     * Interrumpe la sincronizaci\u00f3n en progreso
     */
    public function cancelSync()
    {
        $statusFile = SyncMatrizHomologacion::statusFile();

        if (file_exists($statusFile)) {
            $data = json_decode(file_get_contents($statusFile), true) ?? [];
            if (($data['status'] ?? '') === 'running') {
                $data['status']  = 'cancelled';
                $data['message'] = 'Cancelado por el usuario... deteniendo proceso.';
                file_put_contents($statusFile, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }

        return response()->json(['status' => 'cancelled']);
    }

    /**
     * Exporta la matriz completa (o filtrada) a CSV compatible con Excel.
     * Usa streaming por chunks para no agotar la memoria con cientos de miles de registros.
     */
    public function export(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        // IMPORTANTE: Liberar la sesión escrita inmediatamente.
        // Por defecto, PHP bloquea el archivo de sesión actual hasta que la petición termina.
        // Al generar Excels inmensos, la petición puede durar minutos, "congelando" 
        // por completo cualquier otra pestaña o usuario que comparta esta misma sesión.
        if (session()->isStarted()) {
            session()->save();
        }

        $search    = trim((string) $request->string('q'));
        $filterCol = $request->string('filtro')->toString();
        $filterVal = $request->string('estado')->toString();
        $cobertura = $request->string('cobertura')->toString();
        $tienEn    = array_filter((array) $request->input('tiene_en', []));
        $faltaEn   = array_filter((array) $request->input('falta_en', []));

        $filename = 'homologacion_' . now()->format('Y-m-d_His') . '.xls';

        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        // Obtenemos sucursales dinámicas
        $branchesMap = $this->getDynamicBranches();
        $branchCols = [];
        $branchNames = [];
        $allCols = [];
        foreach ($branchesMap as $name => $info) {
            $branchNames[] = $name;
            $branchCols[]  = $info['col'];
            $allCols[]     = $info['col'];
        }

        $callback = function () use ($search, $filterCol, $filterVal, $cobertura, $tienEn, $faltaEn, $branchNames, $branchCols, $allCols) {
            $out = fopen('php://output', 'w');

            // 1. HTML Headers para engañar a Excel y que retenga CSS en línea
            fwrite($out, '<html xmlns:x="urn:schemas-microsoft-com:office:excel">');
            fwrite($out, '<head><meta charset="utf-8"></head><body>');
            fwrite($out, '<table border="1" style="font-family: Arial, sans-serif; font-size: 11px;">');
            
            // 2. Cabecera Table
            fwrite($out, '<thead><tr>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Código Maestro</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Descripción Universal</th>');
            foreach ($branchNames as $name) {
                fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">' . htmlspecialchars($name) . '</th>');
            }
            fwrite($out, '</tr></thead><tbody>');

            $query = MatrizHomologacion::query();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('clave', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion', 'LIKE', "%{$search}%");
                });
            }

            if ($filterCol && $filterVal) {
                if ($filterVal === 'ACTIVO') {
                    $query->where($filterCol, 1);
                } elseif ($filterVal === 'INACTIVO') {
                    $query->where($filterCol, 0);
                } elseif ($filterVal === 'FALTA') {
                    $query->whereNull($filterCol);
                }
            }

            // Filtros de cobertura en export
            $sumParts = array_map(fn($c) => "COALESCE(`{$c}`, 0)", $allCols);
            $sumExpr  = implode(' + ', $sumParts);
            $total    = count($allCols);
            if ($cobertura === 'todas')            { foreach ($allCols as $c) $query->where($c, 1); }
            elseif ($cobertura === 'ninguna')       { foreach ($allCols as $c) $query->whereNull($c); }
            elseif ($cobertura === 'incompleta')    { $query->whereRaw("({$sumExpr}) > 0")->whereRaw("({$sumExpr}) < {$total}"); }
            elseif ($cobertura === 'solo_una')      { $query->whereRaw("({$sumExpr}) = 1"); }
            elseif ($cobertura === 'todas_menos_una') { $query->whereRaw("({$sumExpr}) = " . ($total - 1)); }

            if (request()->filled('exact')) {
                $exactCount = (int) request()->input('exact');
                $query->whereRaw("({$sumExpr}) = {$exactCount}");
            }
            foreach ($tienEn  as $c) { if (in_array($c, $allCols, true)) $query->where($c, 1); }
            foreach ($faltaEn as $c) { if (in_array($c, $allCols, true)) $query->whereNull($c); }

            // Ordenamiento manual para evitar errores de array-to-string
            $sumExpr = '';
            foreach ($branchCols as $col) {
                $sumExpr .= ($sumExpr === '' ? '' : ' + ') . "COALESCE(`{$col}`, 0)";
            }
            
            if ($sumExpr !== '') {
                $query->orderByRaw("({$sumExpr}) DESC");
            }
            $query->orderBy('clave');

            $query->chunk(500, function ($rows) use ($out, $branchCols) {
                foreach ($rows as $item) {
                    fwrite($out, '<tr>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->clave) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->descripcion) . '</td>');
                    
                    foreach ($branchCols as $col) {
                        $raw = $item->getRawOriginal($col);
                        if ($raw === 1 || $raw === '1') {
                            // ACTIVO -> Fondo Verde
                            fwrite($out, '<td style="background-color:#d1fae5; color:#065f46; text-align:center; font-weight:bold;">ACTIVO</td>');
                        } elseif ($raw === 0 || $raw === '0') {
                            // INACTIVO -> Fondo Naranja
                            fwrite($out, '<td style="background-color:#fef3c7; color:#92400e; text-align:center;">INACTIVO</td>');
                        } else {
                            // FALTA -> Fondo Rojo
                            fwrite($out, '<td style="background-color:#fee2e2; color:#991b1b; text-align:center;">FALTA</td>');
                        }
                    }
                    fwrite($out, '</tr>');
                }
            });

            // Cerrar tabla y HTML
            fwrite($out, '</tbody></table></body></html>');
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Start background export (async)
     */
    public function exportBgStart(Request $request)
    {
        $jobId = uniqid('job_');
        $exportsDir = storage_path('app/exports');
        
        if (!\Illuminate\Support\Facades\File::exists($exportsDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($exportsDir, 0755, true);
        }

        $filters = $request->all();
        $jobData = [
            'status' => 'processing',
            'progress' => 0,
            'total' => 0,
            'processed' => 0,
            'file_url' => null,
            'filters' => $filters,
            'error' => null
        ];

        file_put_contents($exportsDir . '/' . $jobId . '.json', json_encode($jobData, JSON_UNESCAPED_UNICODE));

        // Start background cmd
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $log = storage_path('logs/export_bg.log');

        $cmd = 'start "" /B "' . $php . '" "' . $artisan . '" unidata:export-bg homologacion ' . escapeshellarg($jobId) . ' >> "' . $log . '" 2>&1';
        pclose(popen($cmd, 'r'));

        return response()->json([
            'status' => 'started',
            'job_id' => $jobId
        ]);
    }

    /**
     * Check background export status
     */
    public function exportBgStatus($job_id)
    {
        $file = storage_path('app/exports/' . $job_id . '.json');
        if (!file_exists($file)) {
            return response()->json(['status' => 'error', 'message' => 'Trabajo no encontrado']);
        }
        
        $data = json_decode(file_get_contents($file), true);
        if (!empty($data['file_url'])) {
            $data['file_url'] = asset('exports/' . basename($data['file_url']));
        }
        
        return response()->json($data);
    }
}

