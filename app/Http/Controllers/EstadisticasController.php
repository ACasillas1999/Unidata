<?php

namespace App\Http\Controllers;

use App\Models\MatrizHomologacion;
use App\Models\Branch;
use App\Models\DbMasterArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EstadisticasController extends Controller
{
    public function index(Request $request): View
    {
        $stats       = [];
        $error       = null;
        $branchesArr = [];

        try {
            // ── Sucursales ──────────────────────────────────────────────────
            $branches           = Branch::active()->orderBy('name')->get();
            $totalBranchesCount = $branches->count();

            $physicalCols = array_values(MatrizHomologacion::getPhysicalBranchColumns());

            foreach ($branches as $branch) {
                $col = MatrizHomologacion::resolveColumnName($branch->code);
                // Solo incluir sucursales cuya columna existe en la tabla física
                if (! in_array($col, $physicalCols, true)) {
                    continue;
                }
                $branchesArr[$branch->name] = [
                    'conn' => strtolower($branch->code),
                    'col'  => $col,
                ];
            }

            $totalCount = MatrizHomologacion::count();

            if ($totalCount === 0) {
                $error = 'La matriz está vacía. Ve a Homologación y sincroniza los datos.';
                return view('estadisticas.index', compact('stats', 'branchesArr', 'error'));
            }

            $branchCols = array_column($branchesArr, 'col');
            $sumParts   = array_map(fn($c) => "COALESCE(`{$c}`, 0)", $branchCols);
            $sumExpr    = count($sumParts) > 0 ? implode(' + ', $sumParts) : '0';

            // ════════════════════════════════════════════════════════════════
            // MÉTRICAS BASE
            // ════════════════════════════════════════════════════════════════
            $stats['universo']       = $totalCount;
            $stats['total']          = $totalCount;
            $stats['total_branches'] = $totalBranchesCount;

            // Cobertura por sucursal
            $cols = [];
            foreach ($branchesArr as $name => $info) {
                $cnt = MatrizHomologacion::where($info['col'], true)->count();
                $stats[strtolower($info['conn'])] = $cnt;
                $cols[$name] = $cnt;
            }
            $stats['branch_coverage'] = $cols;

            // Distribución por # de sucursales (para histograma y doughnut)
            $distRaw = MatrizHomologacion::selectRaw("({$sumExpr}) as active_count, COUNT(*) as total")
                ->groupBy('active_count')
                ->pluck('total', 'active_count')
                ->toArray();

            $stats['en_todas']   = $distRaw[$totalBranchesCount] ?? 0;
            $stats['en_ninguna'] = $distRaw[0] ?? 0;
            $casiTodas = $parcial = $baja = 0;
            for ($i = 1; $i < $totalBranchesCount; $i++) {
                $p = ($i / $totalBranchesCount) * 100;
                if ($p >= 80)     $casiTodas += ($distRaw[$i] ?? 0);
                elseif ($p >= 30) $parcial   += ($distRaw[$i] ?? 0);
                else              $baja       += ($distRaw[$i] ?? 0);
            }
            $stats['casi_todas']   = $casiTodas;
            $stats['parcial']      = $parcial;
            $stats['baja']         = $baja;
            $stats['distribucion'] = $distRaw;

            $totalPresencias = array_sum($cols);
            $maxPosible = $totalCount * $totalBranchesCount;
            $stats['cobertura_global_pct'] = $maxPosible > 0
                ? round(($totalPresencias / $maxPosible) * 100, 1) : 0;

            // ════════════════════════════════════════════════════════════════
            // DATOS PARA GRÁFICAS
            // ════════════════════════════════════════════════════════════════

            // Gráfica 3 — Radar: cobertura % por sucursal (normalizada 0-100)
            $radarData = [];
            foreach ($cols as $name => $cnt) {
                $radarData[$name] = $totalCount > 0 ? round(($cnt / $totalCount) * 100, 1) : 0;
            }
            $stats['radar_data'] = $radarData;

            // Gráfica 4 — Horizontal bar top 10 líneas por artículos
            $stats['lineas_chart'] = MatrizHomologacion::selectRaw('SUBSTRING(clave, 1, 5) as linea, COUNT(*) as total')
                ->whereNotNull('clave')->where('clave', '!=', '')
                ->groupByRaw('SUBSTRING(clave, 1, 5)')->orderByDesc('total')->limit(10)
                ->pluck('total', 'linea')->toArray();

            // Gráfica 5 — Histograma de presencias (cuántos artículos en exactamente N sucursales)
            $histograma = [];
            for ($i = 0; $i <= $totalBranchesCount; $i++) {
                $histograma[$i] = $distRaw[$i] ?? 0;
            }
            $stats['histograma'] = $histograma;

            // Gráfica extra — Estado del catálogo (doughnut: habilitados/deshabilitados/criticos/promo)
            // (uses inventario data below)

            // ════════════════════════════════════════════════════════════════
            // DATOS PARA TABLAS
            // ════════════════════════════════════════════════════════════════

            // Tabla 1 — Cobertura por Línea
            $stats['por_linea'] = MatrizHomologacion::selectRaw("
                    SUBSTRING(clave, 1, 5) as linea,
                    COUNT(*) as total,
                    SUM(({$sumExpr})) as presencias,
                    COUNT(CASE WHEN habilitado = 1 THEN 1 END) as habilitados_linea
                ")
                ->whereNotNull('clave')->where('clave', '!=', '')
                ->groupByRaw('SUBSTRING(clave, 1, 5)')->orderByDesc('total')->limit(15)->get()
                ->map(fn($r) => [
                    'linea'           => $r->linea,
                    'total'           => (int)$r->total,
                    'presencias'      => (int)$r->presencias,
                    'habilitados'     => (int)$r->habilitados_linea,
                    'pct'             => $totalBranchesCount > 0 && $r->total > 0
                        ? round(($r->presencias / ($r->total * $totalBranchesCount)) * 100, 1) : 0,
                ])->toArray();


            // ════════════════════════════════════════════════════════════════
            // DB MASTER CONTRASTE
            // ════════════════════════════════════════════════════════════════
            $matrixClaves = MatrizHomologacion::pluck('clave')->toArray();
            $masterClaves = DbMasterArticle::pluck('clave')->toArray();

            // 1. En Master pero no en Matriz (Nuevos en catálogo maestro que no se han procesado)
            $missingInMatrixKeys = array_slice(array_diff($masterClaves, $matrixClaves), 0, 15);
            $stats['missing_in_matrix'] = DbMasterArticle::whereIn('clave', $missingInMatrixKeys)
                ->select('clave', 'descripcion', 'linea', 'precio_lista')
                ->get()->toArray();

            // 2. En Matriz pero no en Master (Registrados en sucursales sin alta global)
            $missingInMasterKeys = array_slice(array_diff($matrixClaves, $masterClaves), 0, 15);
            $stats['missing_in_master'] = MatrizHomologacion::whereIn('clave', $missingInMasterKeys)
                ->selectRaw("clave, descripcion, linea, precio_lista, ({$sumExpr}) as presencias")
                ->get()->toArray();

            // Tabla 4 — Ranking de sucursales (siempre disponible vía branch_coverage)
            // Se calcula después de health_scores — se genera abajo.

            // Tabla 5 — Brecha de cobertura por línea (slots faltantes por línea)
            // Derivada de por_linea ya calculada — se procesa abajo.

            // Tabla 6 — Distribución detallada (histograma como tabla enriquecida)
            // Derivada de distRaw — se procesa abajo.

            // Tabla extra — 100% Homologados
            $stats['top_homologados'] = MatrizHomologacion::selectRaw("
                    clave, descripcion, linea, clasificacion,
                    precio_lista, ({$sumExpr}) as presencias
                ")
                ->whereRaw("({$sumExpr}) = {$totalBranchesCount}")
                ->orderBy('clave')
                ->limit(15)->get()->toArray();

            // ════════════════════════════════════════════════════════════════
            // INVENTARIO GLOBAL
            // ════════════════════════════════════════════════════════════════
            $inv = MatrizHomologacion::selectRaw('
                SUM(existencia_teorica)  as exist_teorica,
                SUM(existencia_fisica)   as exist_fisica,
                AVG(costo_promedio)      as costo_prom,
                SUM(precio_lista)        as valor_lista,
                COUNT(CASE WHEN habilitado = 1 THEN 1 END)                       as habilitados,
                COUNT(CASE WHEN habilitado = 0 OR habilitado IS NULL THEN 1 END) as deshabilitados,
                COUNT(CASE WHEN critico = 1 THEN 1 END)                          as criticos_cnt,
                COUNT(CASE WHEN en_promocion = 1 THEN 1 END)                     as promo_cnt,
                COUNT(CASE WHEN articulo_kit = 1 THEN 1 END)                     as kits_cnt,
                COUNT(CASE WHEN articulo_serie = 1 THEN 1 END)                   as series_cnt,
                COUNT(CASE WHEN sustituto IS NOT NULL AND sustituto != "" THEN 1 END) as con_sustituto
            ')->first();

            $stats['inventario'] = [
                'existencia_teorica' => (float)($inv->exist_teorica   ?? 0),
                'existencia_fisica'  => (float)($inv->exist_fisica    ?? 0),
                'costo_prom_global'  => (float)($inv->costo_prom      ?? 0),
                'valor_lista_total'  => (float)($inv->valor_lista     ?? 0),
                'habilitados'        => (int)($inv->habilitados       ?? 0),
                'deshabilitados'     => (int)($inv->deshabilitados    ?? 0),
                'criticos_count'     => (int)($inv->criticos_cnt      ?? 0),
                'en_promocion_count' => (int)($inv->promo_cnt         ?? 0),
                'kits_count'         => (int)($inv->kits_cnt          ?? 0),
                'series_count'       => (int)($inv->series_cnt        ?? 0),
                'con_sustituto'      => (int)($inv->con_sustituto     ?? 0),
            ];

            // ════════════════════════════════════════════════════════════════
            // HEALTH SCORES por sucursal
            // ════════════════════════════════════════════════════════════════
            $healthScores = [];
            foreach ($branchesArr as $bName => $bInfo) {
                $cnt = $cols[$bName] ?? 0;
                $pct = $totalCount > 0 ? round(($cnt / $totalCount) * 100, 1) : 0;
                $healthScores[$bName] = [
                    'count' => $cnt,
                    'pct'   => $pct,
                    'score' => $pct >= 80 ? 'excelente' : ($pct >= 50 ? 'bueno' : ($pct >= 20 ? 'regular' : 'bajo')),
                ];
            }
            arsort($healthScores);
            $stats['health_scores'] = $healthScores;

            // ════════════════════════════════════════════════════════════════
            // NUEVAS TABLAS DERIVADAS (Sin requerir campos específicos vacíos)
            // ════════════════════════════════════════════════════════════════

            // Tabla 4 — Ranking de Sucursales (ya ordenado por salud)
            $ranking = [];
            $pos = 1;
            foreach ($healthScores as $bName => $hs) {
                // Cuantos articulos le faltan para tener 100% (universo)
                $faltantes = $totalCount - $hs['count'];
                $ranking[] = [
                    'posicion'  => $pos++,
                    'sucursal'  => $bName,
                    'count'     => $hs['count'],
                    'pct'       => $hs['pct'],
                    'faltantes' => $faltantes,
                    'score'     => $hs['score']
                ];
            }
            $stats['ranking_sucursales'] = $ranking;

            // Tabla 5 — Brecha por Línea (Líneas con más artículos faltantes en total)
            $brecha = [];
            foreach ($stats['por_linea'] as $row) {
                $maxPosibles = $row['total'] * $totalBranchesCount;
                $faltantes = $maxPosibles - $row['presencias'];
                $brecha[] = [
                    'linea'       => $row['linea'],
                    'total_arts'  => $row['total'],
                    'presencias'  => $row['presencias'],
                    'max_posible' => $maxPosibles,
                    'faltantes'   => $faltantes,
                    // Porcentaje de brecha: qué tanto de lo que debería haber, no hay
                    'brecha_pct'  => $maxPosibles > 0 ? round(($faltantes / $maxPosibles) * 100, 1) : 0
                ];
            }
            // Ordenamos por mayor número de faltantes
            usort($brecha, fn($a, $b) => $b['faltantes'] <=> $a['faltantes']);
            $stats['brecha_linea'] = array_slice($brecha, 0, 15);

            // Tabla 6 — Distribución Detallada (Tabla de Histograma)
            $detalleDist = [];
            for ($i = $totalBranchesCount; $i >= 0; $i--) {
                $c = $distRaw[$i] ?? 0;
                $detalleDist[] = [
                    'sucursales' => $i,
                    'articulos'  => $c,
                    'pct'        => $totalCount > 0 ? round(($c / $totalCount) * 100, 1) : 0
                ];
            }
            $stats['distribucion_detalle'] = $detalleDist;
            $stats['health_scores'] = $healthScores;

        } catch (\Throwable $e) {
            $error = 'Error cargando estadísticas: ' . $e->getMessage();
        }

        return view('estadisticas.index', [
            'stats'    => $stats,
            'branches' => $branchesArr,
            'error'    => $error,
        ]);
    }
}
