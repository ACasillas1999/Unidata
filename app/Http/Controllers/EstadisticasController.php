<?php

namespace App\Http\Controllers;

use App\Models\MatrizHomologacion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstadisticasController extends Controller
{
    private const BRANCHES = [
        'DEASA'      => ['conn' => 'deasa',      'col' => 'en_deasa'],
        'AIESA'      => ['conn' => 'aiesa',      'col' => 'en_aiesa'],
        'CEDIS'      => ['conn' => 'cedis',      'col' => 'en_cedis'],
        'DIMEGSA'    => ['conn' => 'dimegsa',    'col' => 'en_dimegsa'],
        'FESA'       => ['conn' => 'fesa',       'col' => 'en_fesa'],
        'GABSA'      => ['conn' => 'gabsa',      'col' => 'en_gabsa'],
        'ILU'        => ['conn' => 'ilu',        'col' => 'en_ilu'],
        'QUERÉTARO'  => ['conn' => 'queretaro',  'col' => 'en_queretaro'],
        'SEGSA'      => ['conn' => 'segsa',      'col' => 'en_segsa'],
        'TAPATÍA'    => ['conn' => 'tapatia',    'col' => 'en_tapatia'],
        'VALLARTA'   => ['conn' => 'vallarta',   'col' => 'en_vallarta'],
        'WASHINGTON' => ['conn' => 'washington', 'col' => 'en_washington'],
    ];

    public function index(Request $request): View
    {
        $stats = [];
        $error = null;

        try {
            $totalCount = MatrizHomologacion::count();
            if ($totalCount > 0) {
                $stats['total'] = MatrizHomologacion::where('en_deasa', true)->count(); // Assumed central inventory is Deasa
                
                foreach (self::BRANCHES as $b) {
                    $stats[strtolower($b['conn'])] = MatrizHomologacion::where($b['col'], true)->count();
                }
                $stats['universo'] = $totalCount;
                // Distribución de Cobertura mediante un solo query de agrupación (súper veloz)
                $branchCols = array_column(self::BRANCHES, 'col');
                $sumParts = array_map(fn($c) => "COALESCE(`{$c}`, 0)", $branchCols);
                $sumExpr = implode(' + ', $sumParts);

                $distRaw = MatrizHomologacion::selectRaw("({$sumExpr}) as active_count, COUNT(*) as total")
                    ->groupBy('active_count')
                    ->pluck('total', 'active_count')
                    ->toArray();

                $stats['en_todas'] = $distRaw[12] ?? 0;
                $stats['casi_todas'] = ($distRaw[11] ?? 0) + ($distRaw[10] ?? 0) + ($distRaw[9] ?? 0) + ($distRaw[8] ?? 0);
                $stats['parcial'] = ($distRaw[7] ?? 0) + ($distRaw[6] ?? 0) + ($distRaw[5] ?? 0) + ($distRaw[4] ?? 0) + ($distRaw[3] ?? 0);
                $stats['baja'] = ($distRaw[2] ?? 0) + ($distRaw[1] ?? 0);
                $stats['en_ninguna'] = $distRaw[0] ?? 0;

                // Pasar el arreglo raw para armar las tablas
                $stats['distribucion'] = $distRaw;

            } else {
                $error = 'La matriz está vacía. Ve a Homologación y sincroniza los datos.';
            }

        } catch (\Throwable $e) {
            $error = 'Error cargando estadísticas: ' . $e->getMessage();
        }

        return view('estadisticas.index', [
            'stats'    => $stats,
            'branches' => self::BRANCHES,
            'error'    => $error
        ]);
    }
}
