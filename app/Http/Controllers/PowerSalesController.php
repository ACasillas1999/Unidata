<?php

namespace App\Http\Controllers;

use App\Services\PowerSalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PowerSalesController extends Controller
{
    public function __construct(
        protected PowerSalesService $powerSales
    ) {}

    /**
     * Auditoria: que se mando a PowerSales, cuando, y que contesto.
     */
    public function index(Request $request): View
    {
        $entity = $request->string('entity')->toString();
        $status = $request->string('status')->toString(); // ok | error | ''
        $q      = trim((string) $request->string('q'));

        $query = DB::table('powersales_sync_logs')->orderByDesc('created_at');

        if ($entity !== '') {
            $query->where('entity', $entity);
        }
        if ($status === 'ok') {
            $query->where('success', true);
        } elseif ($status === 'error') {
            $query->where('success', false);
        }
        if ($q !== '') {
            $query->where('referencia', 'LIKE', "%{$q}%");
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('powersales.auditoria', [
            'logs'   => $logs,
            'entity' => $entity,
            'status' => $status,
            'q'      => $q,
        ]);
    }

    /**
     * Vista de solo lectura del mapeo de campos configurado en proteo_db (mismo que /mapeo en Proteo).
     *
     * Proteo separa visualmente 4 categorias aunque en la tabla solo existan 3 valores de `entity`:
     * los campos "PL_*" de entity=articulo son la pestana "Listas de Precios" por separado.
     */
    public function mapeo(): View
    {
        return view('powersales.mapeo', [
            'groups' => $this->powerSales->mappingGroups(),
        ]);
    }

    /**
     * Vista de mapeo geográfico (Estados y Ciudades) entre Magic y PowerSales.
     */
    public function geografia(Request $request): View
    {
        $tab          = $request->input('tab', 'estados'); // 'estados' | 'ciudades'
        $q            = trim((string) $request->input('q', ''));
        $estadoFilter = trim((string) $request->input('estado', ''));
        $statusFilter = trim((string) $request->input('status', 'all')); // 'all' | 'mapped' | 'unmapped'

        // Asegurar que las tablas locales tengan los catálogos base de Magic
        if (\App\Models\PowerSalesMappingEstado::count() === 0) {
            $this->powerSales->syncMagicGeografia();
        }

        // Estadísticas generales
        $stats = [
            'estados_total'       => \App\Models\PowerSalesMappingEstado::count(),
            'estados_mapped'      => \App\Models\PowerSalesMappingEstado::whereNotNull('ps_state_id')->count(),
            'estados_unmapped'    => \App\Models\PowerSalesMappingEstado::whereNull('ps_state_id')->count(),
            'ciudades_total'      => \App\Models\PowerSalesMappingCiudad::count(),
            'ciudades_mapped'     => \App\Models\PowerSalesMappingCiudad::whereNotNull('ps_city_id')->count(),
            'ciudades_unmapped'   => \App\Models\PowerSalesMappingCiudad::whereNull('ps_city_id')->count(),
        ];

        // Catálogos de PowerSales
        $psStates = $this->powerSales->fetchPowerSalesStates();
        $psCities = $this->powerSales->fetchPowerSalesCities();

        // Lista de estados Magic para el filtro del tab de ciudades
        $magicEstadosList = \App\Models\PowerSalesMappingEstado::orderBy('magic_descripcion')->get();

        // Consulta de Estados
        $estadosQuery = \App\Models\PowerSalesMappingEstado::query()->orderBy('magic_descripcion');
        if ($tab === 'estados') {
            if ($q !== '') {
                $estadosQuery->where(function ($sub) use ($q) {
                    $sub->where('magic_clave', 'LIKE', "%{$q}%")
                        ->orWhere('magic_descripcion', 'LIKE', "%{$q}%")
                        ->orWhere('ps_state_name', 'LIKE', "%{$q}%")
                        ->orWhere('ps_state_number', 'LIKE', "%{$q}%");
                });
            }
            if ($statusFilter === 'mapped') {
                $estadosQuery->whereNotNull('ps_state_id');
            } elseif ($statusFilter === 'unmapped') {
                $estadosQuery->whereNull('ps_state_id');
            }
        }
        $estados = $estadosQuery->paginate(50, ['*'], 'estados_page')->withQueryString();

        // Consulta de Ciudades
        $ciudadesQuery = \App\Models\PowerSalesMappingCiudad::query()
            ->with('estado')
            ->orderBy('magic_dsc_ciudad');

        if ($tab === 'ciudades') {
            if ($estadoFilter !== '') {
                $ciudadesQuery->where('magic_cve_estado', $estadoFilter);
            }
            if ($q !== '') {
                $ciudadesQuery->where(function ($sub) use ($q) {
                    $sub->where('magic_cve_ciudad', 'LIKE', "%{$q}%")
                        ->orWhere('magic_dsc_ciudad', 'LIKE', "%{$q}%")
                        ->orWhere('ps_city_name', 'LIKE', "%{$q}%");
                });
            }
            if ($statusFilter === 'mapped') {
                $ciudadesQuery->whereNotNull('ps_city_id');
            } elseif ($statusFilter === 'unmapped') {
                $ciudadesQuery->whereNull('ps_city_id');
            }
        }
        $ciudades = $ciudadesQuery->paginate(50, ['*'], 'ciudades_page')->withQueryString();

        // Agrupar ciudades por StateId para optimizar el renderizado del select
        $psCitiesByState = collect($psCities)->groupBy('StateId')->toArray();

        return view('powersales.geografia', [
            'tab'              => $tab,
            'q'                => $q,
            'estadoFilter'     => $estadoFilter,
            'statusFilter'     => $statusFilter,
            'stats'            => $stats,
            'estados'          => $estados,
            'ciudades'         => $ciudades,
            'psStates'         => $psStates,
            'psCities'         => $psCities,
            'psCitiesByState'  => $psCitiesByState,
            'magicEstadosList' => $magicEstadosList,
        ]);
    }

    /**
     * Guarda o desvincula el mapeo de un Estado Magic a PowerSales.
     */
    public function mapEstado(Request $request)
    {
        $request->validate([
            'id'          => 'required|integer|exists:powersales_mapping_estados,id',
            'ps_state_id' => 'nullable|integer',
        ]);

        $mapping = \App\Models\PowerSalesMappingEstado::findOrFail($request->input('id'));
        $psStateId = $request->input('ps_state_id');

        if ($psStateId) {
            $psStates = $this->powerSales->fetchPowerSalesStates();
            $found = collect($psStates)->firstWhere('Id', (int)$psStateId);

            $mapping->update([
                'ps_state_id'     => $psStateId,
                'ps_state_name'   => $found['Name'] ?? null,
                'ps_state_number' => $found['StateNumber'] ?? null,
                'ps_states_col'   => $found['StatesCol'] ?? null,
            ]);

            $msg = "Estado '{$mapping->magic_descripcion}' vinculado con '{$mapping->ps_state_name}'.";
        } else {
            $mapping->update([
                'ps_state_id'     => null,
                'ps_state_name'   => null,
                'ps_state_number' => null,
                'ps_states_col'   => null,
            ]);
            $msg = "Estado '{$mapping->magic_descripcion}' desvinculado.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'mapping' => $mapping,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Guarda o desvincula el mapeo de una Ciudad Magic a PowerSales.
     */
    public function mapCiudad(Request $request)
    {
        $request->validate([
            'id'         => 'required|integer|exists:powersales_mapping_ciudades,id',
            'ps_city_id' => 'nullable|integer',
        ]);

        $mapping = \App\Models\PowerSalesMappingCiudad::findOrFail($request->input('id'));
        $psCityId = $request->input('ps_city_id');

        if ($psCityId) {
            $psCities = $this->powerSales->fetchPowerSalesCities();
            $found = collect($psCities)->firstWhere('Id', (int)$psCityId);

            $mapping->update([
                'ps_city_id'     => $psCityId,
                'ps_city_name'   => $found['Name'] ?? null,
                'ps_city_number' => $found['CityNumber'] ?? null,
                'ps_state_id'    => $found['StateId'] ?? null,
            ]);

            $msg = "Ciudad '{$mapping->magic_dsc_ciudad}' vinculada con '{$mapping->ps_city_name}'.";
        } else {
            $mapping->update([
                'ps_city_id'     => null,
                'ps_city_name'   => null,
                'ps_city_number' => null,
                'ps_state_id'    => null,
            ]);
            $msg = "Ciudad '{$mapping->magic_dsc_ciudad}' desvinculada.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'mapping' => $mapping,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Ejecuta auto-mapeo heurístico por similitud de nombres.
     */
    public function autoMatchGeografia(Request $request)
    {
        $tipo = $request->input('tipo', 'estados'); // 'estados' | 'ciudades'
        $magicCveEstado = $request->input('magic_cve_estado');

        if ($tipo === 'ciudades') {
            $matched = $this->powerSales->autoMatchCiudades($magicCveEstado);
            $msg = "Se mapearon automáticamente {$matched} ciudades.";
        } else {
            $matched = $this->powerSales->autoMatchEstados();
            $msg = "Se mapearon automáticamente {$matched} estados.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'matched' => $matched,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Refresca los catálogos desde la API de PowerSales y Magic.
     */
    public function refreshGeografiaCatalogs(Request $request)
    {
        // 1. Refrescar caché de API PowerSales
        $this->powerSales->fetchPowerSalesStates(true);
        $this->powerSales->fetchPowerSalesCities(true);

        // 2. Traer novedades desde Magic
        $res = $this->powerSales->syncMagicGeografia();

        $msg = "Catálogos de PowerSales actualizados. Se verificaron {$res['estados']} estados y {$res['ciudades']} ciudades de Magic.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }
}

