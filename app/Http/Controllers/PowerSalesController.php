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
}
