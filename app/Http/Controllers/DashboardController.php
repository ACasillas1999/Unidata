<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\MatrizHomologacion;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats  = [];
        $error  = null;
        $branches     = collect();
        $branchesArr  = [];

        // ── Sucursales ──────────────────────────────────────────────────────
        try {
            $branches = Branch::active()->orderBy('name')->get();
            $totalBranchesCount = $branches->count();

            foreach ($branches as $branch) {
                $col = MatrizHomologacion::resolveColumnName($branch->code);
                $branchesArr[$branch->name] = [
                    'conn' => strtolower($branch->code),
                    'col'  => $col,
                ];
            }
        } catch (\Throwable $e) {
            $error = 'Error cargando sucursales: ' . $e->getMessage();
            $totalBranchesCount = 0;
        }

        // ── Matriz de Homologación ──────────────────────────────────────────
        try {
            $totalCount = MatrizHomologacion::count();

            if ($totalCount > 0) {
                $stats['total']   = $totalCount;
                $stats['universo'] = $totalCount;

                foreach ($branchesArr as $b) {
                    $stats[strtolower($b['conn'])] = MatrizHomologacion::where($b['col'], true)->count();
                }

                $branchCols = array_column($branchesArr, 'col');
                $sumParts   = array_map(fn($c) => "COALESCE(`{$c}`, 0)", $branchCols);
                $sumExpr    = count($sumParts) > 0 ? implode(' + ', $sumParts) : '0';

                $distRaw = MatrizHomologacion::selectRaw("({$sumExpr}) as active_count, COUNT(*) as total")
                    ->groupBy('active_count')
                    ->pluck('total', 'active_count')
                    ->toArray();

                $stats['en_todas']   = $distRaw[$totalBranchesCount] ?? 0;
                $stats['en_ninguna'] = $distRaw[0] ?? 0;

                $casiTodas = $parcial = $baja = 0;
                for ($i = 1; $i < $totalBranchesCount; $i++) {
                    $pct = ($i / $totalBranchesCount) * 100;
                    if      ($pct >= 80) { $casiTodas += ($distRaw[$i] ?? 0); }
                    elseif  ($pct >= 30) { $parcial   += ($distRaw[$i] ?? 0); }
                    else                 { $baja       += ($distRaw[$i] ?? 0); }
                }

                $stats['casi_todas']     = $casiTodas;
                $stats['parcial']        = $parcial;
                $stats['baja']           = $baja;
                $stats['distribucion']   = $distRaw;
                $stats['total_branches'] = $totalBranchesCount;

                // Porcentaje de cobertura global
                $totalPresencias = array_sum(array_map(
                    fn($b) => $stats[strtolower($b['conn'])] ?? 0,
                    $branchesArr
                ));
                $maxPosible = $totalCount * $totalBranchesCount;
                $stats['cobertura_global_pct'] = $maxPosible > 0
                    ? round(($totalPresencias / $maxPosible) * 100, 1)
                    : 0;
            }
        } catch (\Throwable $e) {
            $error = ($error ? $error . ' | ' : '') . 'Matriz: ' . $e->getMessage();
        }

        // ── Siempre disponibles ──────────────────────────────────────────────
        if (!isset($stats['total_branches'])) {
            $stats['total_branches'] = $totalBranchesCount;
        }

        // ── Usuarios & Roles ────────────────────────────────────────────────
        try {
            $stats['total_usuarios']  = User::count();
            $stats['total_roles']     = Role::count();
            $stats['roles_breakdown'] = User::select('role', DB::raw('COUNT(*) as cnt'))
                ->groupBy('role')
                ->orderByDesc('cnt')
                ->pluck('cnt', 'role')
                ->toArray();
        } catch (\Throwable) {
            $stats['total_usuarios'] = 0;
            $stats['total_roles']    = 0;
            $stats['roles_breakdown'] = [];
        }

        // ── DB Master ───────────────────────────────────────────────────────
        try {
            $stats['db_master_total'] = \App\Models\DbMasterArticle::count();
            $lastSync = \App\Models\DbMasterSyncHistory::latest()->first();
            $stats['db_master_last_sync'] = $lastSync?->created_at?->diffForHumans() ?? 'Nunca';
        } catch (\Throwable) {
            $stats['db_master_total']     = 0;
            $stats['db_master_last_sync'] = 'N/A';
        }

        return view('dashboard.index', [
            'stats'       => $stats,
            'branches'    => $branchesArr,
            'branchesArr' => $branchesArr,
            'allBranches' => $branches,
            'error'       => $error,
        ]);
    }
}
