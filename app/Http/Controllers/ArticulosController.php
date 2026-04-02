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
        $perPage  = 50;

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
        ]);
    }
}
