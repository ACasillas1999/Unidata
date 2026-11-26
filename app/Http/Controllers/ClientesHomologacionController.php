<?php

namespace App\Http\Controllers;

use App\Models\ClienteMaestro;
use App\Services\BranchConnectionManager;
use Illuminate\Http\Request;
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

    /** RFC en mayúsculas sin espacios, guiones ni otros separadores. */
    public static function normalizarRfc(string $rfc): string
    {
        return preg_replace('/[^A-Z0-9&Ñ]/u', '', mb_strtoupper($rfc));
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
