<?php

namespace App\Http\Controllers;

use App\Models\MatrizHomologacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

class HomologacionController extends Controller
{
    /** Sucursales y sus columnas en la nueva base de datos local */
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
        $search    = trim((string) $request->string('q'));
        $filterCol = $request->string('filtro')->toString();
        $filterVal = $request->string('estado')->toString();
        $error     = null;
        $stats     = [];

        try {
            $query = MatrizHomologacion::query();

            // Filtro de Búsqueda Rapida
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('clave', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion', 'LIKE', "%{$search}%");
                });
            }

            // Filtro Exacto de Sucursal
            if ($filterCol && $filterVal) {
                // $filterCol es e.g "en_aiesa"
                $isActivo = ($filterVal === 'ACTIVO');
                $query->where($filterCol, $isActivo);
            }

            // Paginación Directa desde la Master DB Local
            $paginator = $query->orderBy('clave')->paginate(50)->withQueryString();

            // Transformamos los resultados al vuelo para que la vista HTML que ya tenías no sufra NINGÚN cambio
            $paginator->getCollection()->transform(function ($item) {
                $out = new \stdClass();
                $out->Codigo_Deasa = $item->clave;
                $out->Descripcion_Deasa = $item->descripcion;
                foreach (self::BRANCHES as $b) {
                    $val = $item->{$b['col']};
                    if ($val === 1) {
                        $out->{$b['col']} = 'ACTIVO';
                    } elseif ($val === 0) {
                        $out->{$b['col']} = 'INACTIVO';
                    } else {
                        $out->{$b['col']} = 'FALTA';
                    }
                }
                return $out;
            });

            $articles = $paginator;

            // Calculamos Estadísticas
            // Para no sobrecargar la tabla (300k+), contamos localmente individual
            $totalCount = MatrizHomologacion::count();
            if ($totalCount > 0) {
                // Total Deasa
                $stats['total'] = MatrizHomologacion::where('en_deasa', true)->count();
                foreach (self::BRANCHES as $b) {
                    $stats[strtolower($b['conn'])] = MatrizHomologacion::where($b['col'], true)->count();
                }
            } else {
                $error = 'La matriz local está vacía. ¡Dale click en "Sincronizar" arriba a la derecha para llenarla por primera vez!';
            }

        } catch (\Throwable $e) {
            $error = 'Error consultando la matriz (¿Problema de conexión o falta migrar?): ' . $e->getMessage();
            $articles = new LengthAwarePaginator([], 0, 50);
        }

        return view('homologacion.index', [
            'articles'  => $articles ?? new LengthAwarePaginator([], 0, 50),
            'error'     => $error,
            'search'    => $search,
            'filterCol' => $filterCol,
            'filterVal' => $filterVal,
            'branches'  => self::BRANCHES,
            'stats'     => $stats,
        ]);
    }

    /**
     * Motor de Sincronización disparado desde la UI
     */
    public function sync()
    {
        try {
            // Aumentamos el límite de tiempo de PHP a infinito para que soporte la carga masiva
            set_time_limit(0);

            // Aseguramos que la tabla DB realmente exista ejecutando las migraciones
            $migrateStatus = Artisan::call('migrate', ['--force' => true]);
            if ($migrateStatus !== 0) {
                throw new \Exception("Error migrando BD local: " . Artisan::output());
            }

            // Llamamos a nuestro Comando Artisan super-veloz para upserts!
            $syncStatus = Artisan::call('unidata:sync-matriz');
            if ($syncStatus !== 0) {
                throw new \Exception("Error Comando Sync: " . Artisan::output());
            }

            return redirect()->route('homologacion.index')
                ->with('success', '¡Sincronización Transversal completada exitosamente! Se analizaron las 12 sucursales.');

        } catch (\Throwable $e) {
            return redirect()->route('homologacion.index')
                ->with('error', 'Error crítico sincronizando: ' . $e->getMessage());
        }
    }
}
