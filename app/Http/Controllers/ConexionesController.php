<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\BranchConnectionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class ConexionesController extends Controller
{
    public function __construct(
        protected BranchConnectionManager $manager,
    ) {}

    public function index(Request $request): View
    {
        $tableExists = Schema::hasTable('branches');

        $branches = $tableExists
            ? Branch::query()->orderBy('name')->get()
            : collect();

        // Stats rápidos
        $total     = $branches->count();
        $active    = $branches->where('status', 'active')->count();
        $connected = $branches->where('connection_status', 'connected')->count();
        $errors    = $branches->whereIn('connection_status', ['error'])->count();

        return view('conexiones.index', compact(
            'branches',
            'tableExists',
            'total',
            'active',
            'connected',
            'errors',
        ));
    }

    /**
     * Guarda una nueva sucursal/conexión.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:20|unique:branches,code',
            'db_host'     => 'required|string|max:255',
            'db_port'     => 'nullable|integer|min:1|max:65535',
            'db_user'     => 'required|string|max:100',
            'db_password' => 'required|string|max:255',
            'db_database' => 'required|string|max:100',
            'status'      => 'nullable|in:active,inactive',
        ]);

        $data['status']            = $data['status'] ?? 'active';
        $data['db_port']           = $data['db_port'] ?? 3306;
        $data['connection_status'] = 'pending';

        $branch = Branch::create($data);

        return response()->json([
            'ok'     => true,
            'branch' => $branch->makeVisible(['db_host','db_port','db_user','db_database','status','connection_status','id','code','name']),
            'message' => "Conexión \"{$branch->name}\" creada correctamente.",
        ], 201);
    }

    /**
     * Actualiza una sucursal/conexión existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $branch = Branch::findOrFail($id);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'message' => 'Sucursal no encontrada.'], 404);
        }

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => "required|string|max:20|unique:branches,code,{$id}",
            'db_host'     => 'required|string|max:255',
            'db_port'     => 'nullable|integer|min:1|max:65535',
            'db_user'     => 'required|string|max:100',
            'db_password' => 'nullable|string|max:255',
            'db_database' => 'required|string|max:100',
            'status'      => 'nullable|in:active,inactive',
        ]);

        // Si la contraseña viene vacía, no la actualizamos
        if (empty($data['db_password'])) {
            unset($data['db_password']);
        }

        $data['status']  = $data['status'] ?? 'active';
        $data['db_port'] = $data['db_port'] ?? 3306;

        // Si se marca inactiva, limpiar el estado de conexión para no mostrar datos engañosos
        if ($data['status'] === 'inactive') {
            $data['connection_status']    = 'pending';
            $data['last_connection_check'] = null;
        }

        $branch->update($data);

        return response()->json([
            'ok'     => true,
            'branch' => $branch->fresh()->makeVisible(['db_host','db_port','db_user','db_database','status','connection_status','id','code','name']),
            'message' => "Conexión \"{$branch->name}\" actualizada correctamente.",
        ]);
    }

    /**
     * Elimina una sucursal.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $branch = Branch::findOrFail($id);
            $name   = $branch->name;
            $branch->delete();

            return response()->json(['ok' => true, 'message' => "Sucursal \"{$name}\" eliminada."]);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'message' => 'Sucursal no encontrada.'], 404);
        }
    }

    /**
     * Prueba la conexión a una sucursal y devuelve el resultado vía AJAX.
     */
    public function test(Request $request, int $id): JsonResponse
    {
        try {
            $branch = Branch::findOrFail($id);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'message' => 'Sucursal no encontrada.'], 404);
        }

        $start = microtime(true);

        try {
            $this->manager->connect($branch);
            $ms = round((microtime(true) - $start) * 1000, 1);

            return response()->json([
                'ok'      => true,
                'message' => "Conexión exitosa en {$ms} ms",
                'ms'      => $ms,
                'status'  => $branch->fresh()->connection_status,
                'checked' => now()->format('H:i:s'),
            ]);
        } catch (Throwable $e) {
            $ms = round((microtime(true) - $start) * 1000, 1);

            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
                'ms'      => $ms,
                'status'  => 'error',
                'checked' => now()->format('H:i:s'),
            ]);
        }
    }

    /**
     * Prueba todas las sucursales activas (batch).
     */
    public function testAll(): JsonResponse
    {
        if (! Schema::hasTable('branches')) {
            return response()->json(['results' => []]);
        }

        $branches = Branch::query()->where('status', 'active')->get();
        $results  = [];

        foreach ($branches as $branch) {
            $start = microtime(true);
            try {
                $this->manager->connect($branch);
                $ms = round((microtime(true) - $start) * 1000, 1);
                $results[$branch->id] = ['ok' => true, 'ms' => $ms, 'status' => 'connected', 'checked' => now()->format('H:i:s')];
            } catch (Throwable $e) {
                $ms = round((microtime(true) - $start) * 1000, 1);
                $results[$branch->id] = ['ok' => false, 'ms' => $ms, 'status' => 'error', 'message' => $e->getMessage(), 'checked' => now()->format('H:i:s')];
            }
        }

        return response()->json(['results' => $results]);
    }
}
