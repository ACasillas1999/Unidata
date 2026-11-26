<?php

namespace App\Http\Controllers;

use App\Models\ClienteMaestro;
use App\Services\BranchConnectionManager;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Clientes → Homologación de clientes.
 *
 * Busca un cliente directo en las sucursales (no en el maestro), para encontrar los que
 * nunca entraron a clientes_maestro (p. ej. clientes antiguos de Magic con IdGlobal = 0,
 * que SyncClientesMaestro no importa) y ver en qué sucursales existe.
 */
class ClientesHomologacionController extends Controller
{
    use Concerns\ClienteSucursalHelpers;

    /** Expresión SQL del RFC sin espacios ni guiones (sucursales: RFC, maestro: rfc). */
    private const RFC_SQL_SUCURSAL = "REPLACE(REPLACE(UPPER(RFC), ' ', ''), '-', '')";
    private const RFC_SQL_MAESTRO = "REPLACE(REPLACE(UPPER(rfc), ' ', ''), '-', '')";

    /** Mínimo de caracteres para buscar: cada búsqueda recorre la tabla clientes de cada sucursal. */
    private const MIN_BUSQUEDA = 3;

    /** Máximo de renglones por sucursal. */
    private const MAX_POR_SUCURSAL = 50;

    public function __construct(protected BranchConnectionManager $connectionManager)
    {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasPermission('actions.clientes_homologar'), 403);

        $search = trim((string) $request->string('q'));
        $branches = $this->connectionManager->getActiveBranches();
        $clientes = [];
        $erroresSucursal = [];
        $aviso = null;

        if ($search !== '' && mb_strlen($search) < self::MIN_BUSQUEDA) {
            $aviso = 'Escribe al menos ' . self::MIN_BUSQUEDA . ' caracteres para buscar.';
        } elseif ($search !== '') {
            [$clientes, $erroresSucursal, $truncadas] = $this->buscarEnSucursales($search, $branches);
            if ($truncadas) {
                $aviso = 'Hay más de ' . self::MAX_POR_SUCURSAL . ' coincidencias en: ' . implode(', ', $truncadas)
                    . '. Escribe el RFC completo o más de la razón social.';
            }
        }

        return view('clientes.homologacion', [
            'search' => $search,
            'branches' => $branches,
            'clientes' => $clientes,
            'erroresSucursal' => $erroresSucursal,
            'aviso' => $aviso,
        ]);
    }

    /**
     * Busca por RFC (sin importar espacios o guiones: "CVA 050825H75" = "CVA050825H75")
     * o por razón social en cada sucursal y agrupa por RFC normalizado.
     *
     * @return array{0: array<string, array>, 1: array<string, string>, 2: string[]}
     */
    private function buscarEnSucursales(string $search, $branches): array
    {
        $rfcBuscado = self::normalizarRfc($search);
        $clientes = [];
        $errores = [];
        $truncadas = [];

        foreach ($branches as $branch) {
            try {
                $filas = $this->connectionManager->connect($branch->code)
                    ->table('clientes')
                    ->select(['Cliente', 'RFC', 'Razon_Social', 'IdGlobal', 'Status', 'Fecha_Alta'])
                    ->where(function ($q) use ($search, $rfcBuscado) {
                        if ($rfcBuscado !== '') {
                            $q->whereRaw("REPLACE(REPLACE(UPPER(RFC), ' ', ''), '-', '') LIKE ?", ['%' . $rfcBuscado . '%']);
                        }
                        $q->orWhere('Razon_Social', 'LIKE', '%' . $search . '%');
                    })
                    ->orderBy('RFC')
                    ->limit(self::MAX_POR_SUCURSAL + 1)
                    ->get();
            } catch (Throwable $e) {
                $errores[$branch->code] = $this->utf8($e->getMessage());
                continue;
            }

            if ($filas->count() > self::MAX_POR_SUCURSAL) {
                $truncadas[] = strtoupper($branch->name);
                $filas = $filas->take(self::MAX_POR_SUCURSAL);
            }

            foreach ($filas as $fila) {
                $rfc = $this->utf8(trim((string) $fila->RFC));
                $clave = self::normalizarRfc($rfc);
                if ($clave === '') {
                    continue;
                }

                $clientes[$clave] ??= [
                    'rfc' => $rfc,
                    'razon_social' => $this->utf8(trim((string) $fila->Razon_Social)),
                    'sucursales' => [],
                    'maestro' => null,
                    'fecha_alta_min' => null,
                ];

                // Si el mismo RFC sale dos veces en una sucursal, se conserva el primero y se marca
                if (isset($clientes[$clave]['sucursales'][$branch->code])) {
                    $clientes[$clave]['sucursales'][$branch->code]['duplicado'] = true;
                    continue;
                }

                $fecha = $fila->Fecha_Alta ? mb_substr((string) $fila->Fecha_Alta, 0, 10) : null;
                $clientes[$clave]['sucursales'][$branch->code] = [
                    'cliente' => $fila->Cliente,
                    'rfc' => $rfc,
                    'razon_social' => $this->utf8(trim((string) $fila->Razon_Social)),
                    'id_global' => (int) ($fila->IdGlobal ?? 0),
                    'status' => strtoupper(trim((string) $fila->Status)),
                    'fecha_alta' => $fecha,
                    'duplicado' => false,
                ];

                if ($fecha && $fecha > '0000-00-00'
                    && ($clientes[$clave]['fecha_alta_min'] === null || $fecha < $clientes[$clave]['fecha_alta_min'])) {
                    $clientes[$clave]['fecha_alta_min'] = $fecha;
                }
            }
        }

        // ¿Ya está en el maestro? (mismo RFC sin espacios ni guiones)
        if ($clientes) {
            $claves = array_map('strval', array_keys($clientes));
            $maestros = ClienteMaestro::query()
                ->select(['id_global', 'rfc', 'razon_social', 'status'])
                ->whereRaw(
                    "REPLACE(REPLACE(UPPER(rfc), ' ', ''), '-', '') IN (" . implode(',', array_fill(0, count($claves), '?')) . ')',
                    $claves
                )
                ->get();
            foreach ($maestros as $m) {
                $clave = self::normalizarRfc((string) $m->rfc);
                if (isset($clientes[$clave])) {
                    $clientes[$clave]['maestro'] = [
                        'id_global' => (int) $m->id_global,
                        'rfc' => $m->rfc,
                        'status' => $m->status,
                    ];
                }
            }
        }

        ksort($clientes);

        return [$clientes, $errores, $truncadas];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HOMOLOGAR
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Simulación: calcula todo lo que haría la homologación SIN escribir nada.
     * GET /clientes/homologacion/plan?rfc=...&origen=CODIGO
     */
    public function plan(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('actions.clientes_homologar'), 403);

        $rfc = self::normalizarRfc((string) $request->input('rfc'));
        if ($rfc === '') {
            return $this->json(['success' => false, 'message' => 'Falta el RFC.'], 422);
        }

        [$plan] = $this->armarPlan($rfc, $request->input('origen'));

        return $this->json(['success' => true, 'plan' => $plan]);
    }

    /**
     * Homologa: crea en el maestro (si falta), pone el IdGlobal del maestro en las sucursales donde
     * ya existe y da de alta en las sucursales donde falta. El plan se vuelve a calcular aquí, con
     * candado, para no usar datos viejos de la pantalla ni dar el mismo IdGlobal a dos clientes.
     * POST /clientes/homologacion/homologar  (rfc, origen)
     */
    public function homologar(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('actions.clientes_homologar'), 403);

        $rfc = self::normalizarRfc((string) $request->input('rfc'));
        $origen = (string) $request->input('origen');
        if ($rfc === '' || $origen === '') {
            return $this->json(['success' => false, 'message' => 'Faltan el RFC o la sucursal de origen.'], 422);
        }

        try {
            return Cache::lock('clientes_homologar', 120)->block(15, function () use ($rfc, $origen, $request) {
                [$plan, $filaOrigen] = $this->armarPlan($rfc, $origen);

                if ($plan['bloqueo']) {
                    return $this->json(['success' => false, 'message' => $plan['bloqueo'], 'plan' => $plan], 422);
                }

                $resultado = $this->ejecutarPlan($plan, $filaOrigen);

                Log::build(['driver' => 'single', 'path' => storage_path('logs/homologacion_clientes.log')])
                    ->info('Homologación de cliente', [
                        'usuario' => $request->user()?->username ?? $request->user()?->name,
                        'rfc' => $plan['rfc'],
                        'origen' => $plan['origen'],
                        'id_global' => $plan['id_global'],
                        'saltos' => $plan['saltos'],
                        'resultado' => $resultado,
                    ]);

                return $this->json([
                    'success' => $resultado['maestro']['status'] === 'ok',
                    'message' => $resultado['maestro']['status'] === 'ok'
                        ? 'Homologación terminada.'
                        : 'No se pudo crear en el maestro; no se escribió nada en las sucursales.',
                    'plan' => $plan,
                    'resultado' => $resultado,
                ]);
            });
        } catch (LockTimeoutException) {
            return $this->json(['success' => false, 'message' => 'Hay otra homologación en curso. Intenta de nuevo en unos segundos.'], 409);
        }
    }

    /**
     * Arma el plan de homologación de un RFC (normalizado) sin escribir nada.
     *
     * @return array{0: array, 1: ?object} [plan para mostrar, renglón completo de la sucursal de origen]
     */
    private function armarPlan(string $rfc, ?string $origenPedido): array
    {
        $branches = $this->connectionManager->getActiveBranches();
        $sucursales = [];
        $filas = [];      // code => renglón completo (solo sucursales con 1 renglón)
        $conexiones = [];
        $fechas = [];

        foreach ($branches as $branch) {
            $info = ['code' => $branch->code, 'name' => strtoupper($branch->name)];
            try {
                $conn = $this->connectionManager->connect($branch->code);
                $conexiones[$branch->code] = $conn;
                $rows = $conn->table('clientes')
                    ->whereRaw(self::RFC_SQL_SUCURSAL . ' = ?', [$rfc])
                    ->orderBy('Fecha_Alta')
                    ->get();
            } catch (Throwable $e) {
                $sucursales[$branch->code] = $info + ['estado' => 'error', 'accion' => 'error', 'mensaje' => $this->utf8($e->getMessage())];
                continue;
            }

            foreach ($rows as $r) {
                $f = $r->Fecha_Alta ? mb_substr((string) $r->Fecha_Alta, 0, 10) : null;
                if ($f && $f > '0000-00-00') {
                    $fechas[] = $f;
                }
            }

            if ($rows->count() === 0) {
                $sucursales[$branch->code] = $info + ['estado' => 'falta', 'accion' => 'alta'];
            } elseif ($rows->count() > 1) {
                $sucursales[$branch->code] = $info + [
                    'estado' => 'repetido',
                    'accion' => 'bloqueada',
                    'clientes' => $rows->map(fn ($r) => [
                        'cliente' => $r->Cliente,
                        'rfc' => $this->utf8(trim((string) $r->RFC)),
                        'razon_social' => $this->utf8(trim((string) $r->Razon_Social)),
                    ])->all(),
                    'mensaje' => 'El RFC está en ' . $rows->count() . ' clientes (#' . $rows->pluck('Cliente')->implode(', #')
                        . '). Revísalo en Magic y vuelve a homologar.',
                ];
            } else {
                $r = $rows->first();
                $filas[$branch->code] = $r;
                $sucursales[$branch->code] = $info + [
                    'estado' => 'existe',
                    'accion' => null,   // se decide abajo, cuando ya se conoce el IdGlobal
                    'cliente' => $r->Cliente,
                    'rfc' => $this->utf8(trim((string) $r->RFC)),
                    'razon_social' => $this->utf8(trim((string) $r->Razon_Social)),
                    'id_global_actual' => (int) ($r->IdGlobal ?? 0),
                    'status' => strtoupper(trim((string) $r->Status)),
                    'fecha_alta' => $r->Fecha_Alta ? mb_substr((string) $r->Fecha_Alta, 0, 10) : null,
                ];
            }
        }

        $plan = [
            'rfc' => null,
            'razon_social' => null,
            'status' => null,
            'origen' => null,
            'origenes' => array_keys($filas),
            'fecha_alta' => $fechas ? min($fechas) : null,
            'maestro' => null,
            'id_global' => null,
            'saltos' => [],
            'sucursales' => $sucursales,
            'avisos' => [],
            'bloqueo' => null,
        ];

        // ── Sucursal de origen ───────────────────────────────────────────────
        if (!$filas) {
            $plan['bloqueo'] = collect($sucursales)->contains(fn ($s) => $s['estado'] === 'repetido')
                ? 'El RFC está repetido en las sucursales donde existe. Revísalo en Magic.'
                : 'No se encontró el RFC en ninguna sucursal.';

            return [$plan, null];
        }

        if ($origenPedido !== null && $origenPedido !== '') {
            if (!isset($filas[$origenPedido])) {
                $plan['bloqueo'] = "La sucursal de origen {$origenPedido} no tiene este cliente (o lo tiene repetido).";

                return [$plan, null];
            }
            $origen = $origenPedido;
        } else {
            // Por omisión: la de fecha de alta más antigua
            $origen = collect($filas)->sortBy(fn ($r) => (string) ($r->Fecha_Alta ?? '9999'))->keys()->first();
        }

        $filaOrigen = $filas[$origen];
        $plan['origen'] = $origen;
        $plan['rfc'] = $this->utf8(trim((string) $filaOrigen->RFC));
        $plan['razon_social'] = $this->utf8(trim((string) $filaOrigen->Razon_Social));
        $plan['status'] = strtoupper(trim((string) $filaOrigen->Status));

        if ($plan['status'] !== 'A') {
            $plan['avisos'][] = 'El estatus en la sucursal de origen es '
                . ($plan['status'] === '' ? 'vacío (sin estatus)' : "Status Magic ({$plan['status']})")
                . '; se copiará tal cual.';
        }

        // ── Maestro e IdGlobal ───────────────────────────────────────────────
        $maestro = ClienteMaestro::whereRaw(self::RFC_SQL_MAESTRO . ' = ?', [$rfc])->first();
        $plan['maestro'] = $maestro
            ? ['existe' => true, 'id_global' => (int) $maestro->id_global, 'rfc' => $maestro->rfc]
            : ['existe' => false];

        if ($maestro && (int) $maestro->id_global > 0) {
            $plan['id_global'] = (int) $maestro->id_global;
        } else {
            $candidato = (int) (ClienteMaestro::max('id_global') ?? 0) + 1;
            for ($i = 0; $i < 500; $i++, $candidato++) {
                $ocupadoPor = $this->idGlobalOcupado($candidato, $rfc, $conexiones);
                if ($ocupadoPor === null) {
                    break;
                }
                $plan['saltos'][] = "El {$candidato} ya lo usa {$ocupadoPor}; se pasa al siguiente.";
            }
            if ($i === 500) {
                $plan['bloqueo'] = 'No se encontró un IdGlobal libre (500 intentos).';

                return [$plan, $filaOrigen];
            }
            $plan['id_global'] = $candidato;
        }

        // ── Qué se hará en cada sucursal ─────────────────────────────────────
        foreach ($plan['sucursales'] as $code => &$s) {
            if ($s['estado'] === 'existe') {
                $s['accion'] = $s['id_global_actual'] === $plan['id_global'] ? 'ninguna' : 'id_global';
            } elseif ($s['estado'] === 'falta') {
                try {
                    $conn = $conexiones[$code];
                    $s['siguiente_cliente'] = $this->calcularSiguienteClienteSucursal($conn);
                    // Razón social con índice único (KRazon_Social): si otro cliente ya la usa, el alta fallaría
                    $otro = $conn->table('clientes')->where('Razon_Social', $filaOrigen->Razon_Social)->first(['Cliente', 'RFC']);
                    if ($otro) {
                        $s['accion'] = 'bloqueada';
                        $s['mensaje'] = 'Otro cliente (#' . $otro->Cliente . ', RFC ' . $this->utf8(trim((string) $otro->RFC))
                            . ') ya tiene esta razón social. Revísalo en Magic.';
                    }
                } catch (Throwable $e) {
                    $s['accion'] = 'error';
                    $s['mensaje'] = $this->utf8($e->getMessage());
                }
            }
        }
        unset($s);

        return [$plan, $filaOrigen];
    }

    /**
     * ¿Otro cliente (otro RFC) ya usa este IdGlobal en el maestro o en alguna sucursal?
     * Devuelve la descripción de quién lo usa, o null si está libre.
     */
    private function idGlobalOcupado(int $idGlobal, string $rfc, array $conexiones): ?string
    {
        $m = ClienteMaestro::where('id_global', $idGlobal)->first(['rfc']);
        if ($m) {
            return 'el RFC ' . $m->rfc . ' en el maestro';
        }
        foreach ($conexiones as $code => $conn) {
            try {
                $otro = $conn->table('clientes')
                    ->where('IdGlobal', $idGlobal)
                    ->whereRaw(self::RFC_SQL_SUCURSAL . ' <> ?', [$rfc])
                    ->first(['Cliente', 'RFC']);
            } catch (Throwable) {
                continue;
            }
            if ($otro) {
                return 'el cliente #' . $otro->Cliente . ' (RFC ' . $this->utf8(trim((string) $otro->RFC)) . ") en {$code}";
            }
        }

        return null;
    }

    /**
     * Escribe el plan. Primero el maestro; si falla, no se toca ninguna sucursal.
     * En sucursales donde ya existe solo se cambia IdGlobal (número de cliente, saldo y datos se quedan).
     */
    private function ejecutarPlan(array $plan, object $filaOrigen): array
    {
        $idGlobal = $plan['id_global'];
        $resultado = ['maestro' => [], 'sucursales' => []];

        // ── 1. Maestro ───────────────────────────────────────────────────────
        try {
            $maestro = ClienteMaestro::whereRaw(self::RFC_SQL_MAESTRO . ' = ?', [self::normalizarRfc($plan['rfc'])])->first();
            if (!$maestro) {
                $maestro = ClienteMaestro::create($this->datosMaestroDesdeSucursal($filaOrigen, $plan['origen'], $idGlobal, $plan['fecha_alta']));
                $resultado['maestro'] = ['status' => 'ok', 'accion' => 'creado', 'id_global' => $idGlobal];
            } elseif ((int) $maestro->id_global !== $idGlobal) {
                $maestro->update(['id_global' => $idGlobal]);
                $resultado['maestro'] = ['status' => 'ok', 'accion' => 'id_global', 'id_global' => $idGlobal];
            } else {
                $resultado['maestro'] = ['status' => 'ok', 'accion' => 'ya_existia', 'id_global' => $idGlobal];
            }
        } catch (Throwable $e) {
            $resultado['maestro'] = ['status' => 'error', 'mensaje' => $this->utf8($e->getMessage())];

            return $resultado;
        }

        $dataMaestro = $maestro->fresh()->toArray();
        $email = trim((string) ($dataMaestro['email'] ?? ''));
        $branchNames = $this->connectionManager->getActiveBranches()->pluck('name', 'code');

        // ── 2. Sucursales ────────────────────────────────────────────────────
        foreach ($plan['sucursales'] as $code => $s) {
            $base = ['code' => $code, 'name' => $s['name']];

            if (in_array($s['accion'], ['bloqueada', 'error'], true)) {
                $resultado['sucursales'][$code] = $base + ['status' => $s['accion'] === 'error' ? 'error' : 'omitida', 'mensaje' => $s['mensaje'] ?? ''];
                continue;
            }
            if ($s['accion'] === 'ninguna') {
                $resultado['sucursales'][$code] = $base + ['status' => 'ok', 'accion' => 'ya_tenia', 'cliente' => $s['cliente']];
                continue;
            }

            try {
                $conn = $this->connectionManager->connect($code);

                if ($s['accion'] === 'id_global') {
                    $conn->table('clientes')->where('Cliente', $s['cliente'])->update(['IdGlobal' => $idGlobal]);
                    $resultado['sucursales'][$code] = $base + [
                        'status' => 'ok', 'accion' => 'id_global', 'cliente' => $s['cliente'],
                        'de' => $s['id_global_actual'], 'a' => $idGlobal,
                    ];
                    continue;
                }

                // accion === 'alta'
                $columnas = $this->getBranchColumns($conn, 'clientes');
                $data = array_filter($this->toBranchFormat($dataMaestro), fn ($k) => in_array($k, $columnas), ARRAY_FILTER_USE_KEY);

                $codigo = $this->resolveCodigoVendedorAsesor((string) ($branchNames[$code] ?? $s['name']));
                if (in_array('Asesor', $columnas)) {
                    $data['Asesor'] = $codigo;
                }
                if (in_array('Vendedor', $columnas)) {
                    $data['Vendedor'] = strtoupper((string) ($dataMaestro['vendedor'] ?? '')) === 'TOP' ? 'TOP' : $codigo;
                }
                if (in_array('IdGlobal', $columnas)) {
                    $data['IdGlobal'] = $idGlobal;
                }
                if (in_array('Saldo_Actual', $columnas)) {
                    $data['Saldo_Actual'] = 0;
                }
                $data['Cliente'] = $this->calcularSiguienteClienteSucursal($conn);

                $conn->table('clientes')->insert($data);

                if ($email !== '' && $conn->getSchemaBuilder()->hasTable('clientes_email')) {
                    $conn->table('clientes_email')->updateOrInsert(['Clave_Cliente' => (int) $data['Cliente']], ['e_mail' => $email]);
                }

                $resultado['sucursales'][$code] = $base + ['status' => 'ok', 'accion' => 'creado', 'cliente' => $data['Cliente']];
            } catch (Throwable $e) {
                $resultado['sucursales'][$code] = $base + ['status' => 'error', 'mensaje' => $this->utf8($e->getMessage())];
            }
        }

        return $resultado;
    }

    /** Datos del maestro a partir del renglón de la sucursal de origen (mismo mapeo que importarDesdeRamas). */
    private function datosMaestroDesdeSucursal(object $row, string $origen, int $idGlobal, ?string $fechaAlta): array
    {
        $t = fn ($v) => $this->utf8(trim((string) ($v ?? '')));

        $email = '';
        try {
            $conn = $this->connectionManager->connect($origen);
            if ($conn->getSchemaBuilder()->hasTable('clientes_email')) {
                $email = $t($conn->table('clientes_email')->where('Clave_Cliente', (int) $row->Cliente)->value('e_mail'));
            }
        } catch (Throwable) {
        }

        return [
            'id_global' => $idGlobal,
            'rfc' => $t($row->RFC),
            'razon_social' => $t($row->Razon_Social),
            'calle' => $t($row->Calle ?? ''),
            'exterior' => $t($row->Exterior ?? ''),
            'interior' => $t($row->Interior ?? ''),
            'colonia' => $t($row->Colonia ?? ''),
            'cod_postal' => (int) ($row->Cod_Postal ?? 0),
            'ciudad' => $t($row->Ciudad ?? ''),
            'municipio' => $t($row->Municipio ?? ''),
            'telefono1' => $t($row->Telefono1 ?? ''),
            'telefono2' => $t($row->Telfono2 ?? $row->Telefono2 ?? ''),   // en Magic la columna es "Telfono2"
            'telefono3' => $t($row->Telefono3 ?? ''),
            'fax' => $t($row->Fax ?? ''),
            'vendedor' => $t($row->Vendedor ?? ''),
            'documentos' => $t($row->Documentos ?? ''),
            'dias_pago' => $t($row->Dias_Pago ?? ''),
            'dias_revision' => $t($row->Dias_Revision ?? ''),
            'condicion_pago' => $t($row->Condicion_Pago ?? ''),
            'dias_credito' => (int) ($row->Dias_Credito ?? 0),
            'limite_credito' => (float) ($row->Limite_Credito ?? 0),
            'otorgo_credito' => (int) ($row->OtorgoCreditO ?? $row->OtorgoCredito ?? 0),
            'saldo_actual' => (float) ($row->Saldo_Actual ?? 0),
            'status' => $t($row->Status ?? ''),
            'cta_contable' => $t($row->Cta_Contable ?? ''),
            'fecha_alta' => $fechaAlta ?? now()->toDateString(),
            'representante' => $t($row->Representante ?? ''),
            'addenda' => $t($row->Addenda ?? ''),
            'id_reg_id_trib' => $t($row->IdRegIdTrib ?? ''),
            'regimen_fiscal' => $t($row->RegimenFiscal ?? ''),
            'anticipos' => $t($row->Anticipos ?? 'D') ?: 'D',
            'codigo_contpaq' => (int) ($row->CodigoContpaq ?? 0),
            'clasificacion' => (int) ($row->Clasificacion ?? 0),
            'modificar_fpmpfac' => (int) ($row->ModificarFPMPFac ?? 0),
            'identificador' => $t($row->Identificador ?? ''),
            'id_opcion_bloqueo' => (int) ($row->IdOpcionBloqueo ?? 0),
            'observaciones_caja' => $t($row->ObservacionesCaja ?? ''),
            'sync' => 0,
            'prefijo_descripcion' => (int) ($row->PrefijoDescripcion ?? 0) === 1 ? 1 : 0,
            'id_sugar' => $t($row->IdSugar ?? ''),
            'giro_principal' => (int) ($row->GiroPrincipal ?? 0),
            'asesor' => $t($row->Asesor ?? ''),
            'ubicacion' => $t($row->Ubicacion ?? ''),
            'email' => $email,
        ];
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status, ['Content-Type' => 'application/json; charset=UTF-8'],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /** RFC en mayúsculas sin espacios ni guiones (igual que RFC_SQL_SUCURSAL / RFC_SQL_MAESTRO). */
    public static function normalizarRfc(string $rfc): string
    {
        return str_replace([' ', '-'], '', mb_strtoupper(trim($rfc)));
    }

    /** Las sucursales pueden devolver texto en Windows-1252; Blade lo vaciaría si no es UTF-8. */
    private function utf8(string $s): string
    {
        if (mb_check_encoding($s, 'UTF-8')) {
            return $s;
        }
        $c = @mb_convert_encoding($s, 'UTF-8', 'Windows-1252, ISO-8859-1');

        return ($c !== false && mb_check_encoding($c, 'UTF-8')) ? $c : mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    }
}
