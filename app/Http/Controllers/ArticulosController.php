<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ArticulosController extends Controller
{
    /** Conexiones de base de datos disponibles para validación */
    private const BRANCHES = [
        'deasa'      => 'Deasa',
        'aiesa'      => 'Aiesa',
        'cedis'      => 'Cedis',
        'dimegsa'    => 'Dimegsa',
        'fesa'       => 'Fesa',
        'gabsa'      => 'Gabsa',
        'ilu'        => 'Ilu',
        'queretaro'  => 'Querétaro',
        'segsa'      => 'Segsa',
        'tapatia'    => 'Tapatía',
        'vallarta'   => 'Vallarta',
        'washington' => 'Washington',
    ];

    public function index(Request $request): View
    {
        $search   = trim((string) $request->string('q'));
        $sucursal = $request->string('sucursal')->toString() ?: 'deasa';
        $perPage  = (int) $request->input('per_page', 50);
        if (!in_array($perPage, [50, 100, 250, 500])) $perPage = 50;

        // Validar que la sucursal seleccionada es válida
        if (!array_key_exists($sucursal, self::BRANCHES)) {
            $sucursal = 'deasa';
        }

        $error    = null;
        $articles = new Paginator([], $perPage);

        try {
            $query = DB::connection($sucursal)
                ->table('articulo')
                ->select('Clave_Articulo', 'Descripcion', 'Habilitado');

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
            $error = 'Fallo de conexión en sucursal ' . self::BRANCHES[$sucursal] . ': ' . $e->getMessage();
        }

        return view('articulos.index', [
            'branches' => self::BRANCHES,
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

        $search   = trim((string) $request->string('q'));
        $sucursal = $request->string('sucursal')->toString() ?: 'deasa';

        if (!array_key_exists($sucursal, self::BRANCHES)) {
            $sucursal = 'deasa';
        }

        $branchName = self::BRANCHES[$sucursal];
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
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Código (' . $branchName . ')</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Descripción</th>');
            fwrite($out, '<th style="background:#1e293b; color:#ffffff; font-weight:bold; padding:8px;">Estatus</th>');
            fwrite($out, '</tr></thead><tbody>');

            $query = DB::connection($sucursal)
                ->table('articulo')
                ->select('Clave_Articulo', 'Descripcion', 'Habilitado');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('Clave_Articulo', 'LIKE', "%{$search}%")
                      ->orWhere('Descripcion', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy('Clave_Articulo', 'asc');

            // DB::connection->chunk is safer memory-wise
            $query->chunkById(500, function ($rows) use ($out) {
                foreach ($rows as $item) {
                    fwrite($out, '<tr>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Clave_Articulo) . '</td>');
                    fwrite($out, '<td style="vertical-align:middle;">' . htmlspecialchars((string)$item->Descripcion) . '</td>');
                    
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
}
