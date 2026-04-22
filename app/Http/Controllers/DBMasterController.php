<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Console\Commands\SyncDbMaster;
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

        $error    = null;
        $articles = new LengthAwarePaginator([], 0, $perPage);
        $stats    = ['universo' => 0, 'last_sync' => 'Nunca'];
        $branches = [];

        try {
            $branches = $this->getDynamicBranches();
            $query = DbMasterArticle::query();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('clave', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy('clave');
            $paginator = $query->paginate($perPage)->withQueryString();

            $paginator->getCollection()->transform(function ($item) use ($branches) {
                $out = (object) $item->toArray();
                $out->Codigo_Deasa      = $item->clave;
                $out->Descripcion_Deasa = $item->descripcion;
                foreach ($branches as $info) {
                    $out->{$info['col']} = 'ACTIVO';
                }
                return $out;
            });

            $articles = $paginator;
            $stats['universo'] = $paginator->total();

            $lastSync = DbMasterSyncHistory::orderBy('created_at', 'DESC')->first();
            $stats['last_sync'] = $lastSync ? $lastSync->created_at->format('d/m/Y H:i') : 'Nunca';

        } catch (\Throwable $e) {
            file_put_contents(storage_path('logs/sync_dbmaster_error.log'), $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            $error = 'Error consultando la base de datos maestra: ' . $e->getMessage();
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
     * Inicia la sincronización en background (no bloquea el request)
     */
    public function sync()
    {
        $statusFile = SyncDbMaster::statusFile();

        // Si ya hay una corriendo, no duplicar
        if (file_exists($statusFile)) {
            $data = json_decode(file_get_contents($statusFile), true);
            if (isset($data['status']) && $data['status'] === 'running') {
                return response()->json(['status' => 'already_running', 'message' => 'Ya hay una sincronización en progreso.']);
            }
        }

        // Estado inicial
        file_put_contents($statusFile, json_encode([
            'status'     => 'running',
            'message'    => 'Iniciando sincronización...',
            'step'       => 0,
            'total'      => 0,
            'updated_at' => time(),
        ], JSON_UNESCAPED_UNICODE));

        // Lanzar proceso de fondo (no muere si cierras la pestaña)
        $php     = PHP_BINARY;
        $artisan = base_path('artisan');
        $log     = storage_path('logs') . DIRECTORY_SEPARATOR . 'sync_dbmaster_bg.log';
        $cmd     = 'start "" /B "' . $php . '" "' . $artisan . '" unidata:sync-dbmaster >> "' . $log . '" 2>&1';
        pclose(popen($cmd, 'r'));

        return response()->json(['status' => 'started']);
    }

    /**
     * Polling: retorna JSON con el estado actual del proceso de fondo
     */
    public function syncStatus()
    {
        $statusFile = SyncDbMaster::statusFile();
        if (!file_exists($statusFile)) {
            return response()->json(['status' => 'idle', 'message' => 'Sin sincronización reciente.', 'step' => 0, 'total' => 0]);
        }

        return response()->json(
            json_decode(file_get_contents($statusFile), true) ?? []
        );
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
            file_put_contents(storage_path('logs/sync_dbmaster_error.log'), $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exporta la base maestra a XLS
     */
    public function export(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $filename = 'DB_Master_Articulos_' . now()->format('Y-m-d_His') . '.xls';
        $headers  = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            echo "<table>";
            echo "<tr><th>Clave</th><th>Descripción</th></tr>";
            DbMasterArticle::query()->chunk(500, function ($rows) {
                foreach ($rows as $article) {
                    echo "<tr><td>{$article->clave}</td><td>{$article->descripcion}</td></tr>";
                }
            });
            echo "</table>";
        };

        return response()->stream($callback, 200, $headers);
    }
}
