<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ClienteCampo;
use App\Models\ClienteCampoSucursal;
use App\Models\ClienteMaestro;
use App\Services\BranchConnectionManager;
use App\Services\PowerSalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ClientesController extends Controller
{
    public function __construct(
        protected BranchConnectionManager $connectionManager,
        protected PowerSalesService $powerSales
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // LISTADO
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $search  = trim((string) $request->string('q'));
        $perPage = (int) $request->input('per_page', 50);
        if (!in_array($perPage, [50, 100, 250, 500])) $perPage = 50;

        $query = ClienteMaestro::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('rfc', 'LIKE', "%{$search}%")
                  ->orWhere('razon_social', 'LIKE', "%{$search}%");
            });
        }

        $clientes = $query->whereNotNull('rfc')->where('rfc', '!=', '')->orderBy('razon_social')->paginate($perPage)->withQueryString();

        return view('clientes.index', [
            'search'   => $search,
            'clientes' => $clientes,
            'per_page' => $perPage,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREAR
    // ─────────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        // Solo obtener campos configurados para Crear
        $campos = ClienteCampo::where('show_in_create', true)->orderBy('id')->get();
        $catalogs = config('client_catalogs');
        return view('clientes.crear', compact('campos', 'catalogs'));
    }

    public function store(Request $request)
    {
        $rfc = strtoupper(trim($request->input('rfc', '')));

        // 1. Detectar RFC duplicado ANTES de validar → ofrecer editar
        $existente = ClienteMaestro::where('rfc', $rfc)->first();
        if ($existente) {
            return redirect()->route('clientes.edit', $rfc)
                ->with('warning', "El RFC <strong>{$rfc}</strong> ya existe en el maestro. Puedes editarlo aquí y los cambios se propagarán a todas las sucursales.");
        }

        // 2. Validación básica
        $request->validate([
            'rfc'          => 'required|string|max:14',
            'razon_social' => 'required|string|max:255',
            'status'       => 'required|string|max:1',
        ]);

        $branches = $this->connectionManager->getActiveBranches();

        // 2. Calcular siguiente IdGlobal de forma segura
        $maxGlobal = 0;

        // Revisar maestro
        $maxMaestro = ClienteMaestro::max('id_global') ?? 0;
        $maxGlobal  = max($maxGlobal, (int) $maxMaestro);

        // Revisar cada sucursal activa
        foreach ($branches as $branch) {
            try {
                $conn      = $this->connectionManager->connect($branch->code);
                $maxSuc    = $conn->table('clientes')->max('IdGlobal') ?? 0;
                $maxGlobal = max($maxGlobal, (int) $maxSuc);
            } catch (Throwable) {
                // Sucursal inaccesible, continuar
            }
        }

        $nuevoIdGlobal = $maxGlobal + 1;

        // 3. Preparar datos (snake_case para maestro)
        $dataMaestro = $this->prepareData($request, $nuevoIdGlobal);

        // 4. Insertar en maestro dentro de transacción
        $maestro = ClienteMaestro::create($dataMaestro);

        // 5. Insertar en todas las sucursales activas
        $resultados = [];
        foreach ($branches as $branch) {
            try {
                $conn       = $this->connectionManager->connect($branch->code);
                $dataBranch = $this->toBranchFormat($dataMaestro);

                // Obtener columnas reales de la tabla en esta sucursal
                $columnasReales = $this->getBranchColumns($conn, 'clientes');

                // Filtrar solo los campos que existen en esta sucursal
                $dataFiltrada = array_filter(
                    $dataBranch,
                    fn($key) => in_array($key, $columnasReales),
                    ARRAY_FILTER_USE_KEY
                );

                // Mapeo específico para vendedores/asesores (por límite de caracteres del ERP)
                $codigoVendedorAsesor = (strtoupper($branch->name) === 'TAPATIA') ? 'EITSA' : strtoupper($branch->name);

                // Regla estricta para Asesor y Vendedor
                if (in_array('Asesor', $columnasReales)) {
                    $dataFiltrada['Asesor'] = $codigoVendedorAsesor;
                }
                
                if (in_array('Vendedor', $columnasReales)) {
                    // Si el maestro dice TOP, se respeta TOP. Si no, va el nombre de la sucursal.
                    $dataFiltrada['Vendedor'] = (strtoupper($dataMaestro['vendedor'] ?? '') === 'TOP') 
                                              ? 'TOP' 
                                              : $codigoVendedorAsesor;
                }

                // Calcular el siguiente ID local (Cliente no es auto-increment, lo gestiona el ERP)
                if (in_array('Cliente', $columnasReales)) {
                    $maxCliente = $conn->table('clientes')->max('Cliente') ?? 0;
                    $dataFiltrada['Cliente'] = (int)$maxCliente + 1;
                }

                $conn->table('clientes')->insert($dataFiltrada);
                $localId = $dataFiltrada['Cliente'] ?? null;

                $resultados[] = [
                    'sucursal' => $branch->name,
                    'status'   => 'ok',
                    'local_id' => $localId,
                ];
            } catch (Throwable $e) {
                $resultados[] = [
                    'sucursal' => $branch->name,
                    'status'   => 'error',
                    'message'  => $e->getMessage(),
                ];
            }
        }

        // Sync a PowerSales (no bloquea ni revierte si falla; ver storage/logs/powersales.log)
        $this->powerSales->syncCliente($this->toBranchFormat($dataMaestro));

        $exitosos = count(array_filter($resultados, fn($r) => $r['status'] === 'ok'));
        $total    = count($resultados);

        // Construir detalle de errores para diagnóstico
        $errores = array_filter($resultados, fn($r) => $r['status'] === 'error');
        $detalleError = '';
        foreach ($errores as $err) {
            $detalleError .= " | [{$err['sucursal']}]: {$err['message']}";
        }

        return redirect()->route('clientes.index')
            ->with('success', "Cliente #{$nuevoIdGlobal} creado exitosamente en {$exitosos}/{$total} sucursales." . ($detalleError ? " ERRORES:{$detalleError}" : ''))
            ->with('resultados', $resultados);

    }

    // ─────────────────────────────────────────────────────────────────────────
    // EDITAR
    // ─────────────────────────────────────────────────────────────────────────

    public function edit(string $rfc): View
    {
        // Buscar en maestro; si no existe, importar desde sucursales
        $cliente = ClienteMaestro::where('rfc', $rfc)->first();

        if (!$cliente) {
            $cliente = $this->importarDesdeRamas($rfc);
        }

        if (!$cliente) {
            abort(404, "Cliente {$rfc} no encontrado en ninguna sucursal ni en maestro.");
        }

        // Obtener TODOS los campos para mostrarlos (si show_in_edit es false, se mostrarán como readonly en la vista)
        $campos = ClienteCampo::orderBy('id')->get();

        // Consultar estado en cada sucursal
        $estadoSucursales = $this->resolveEstadoSucursales($cliente);

        $catalogs = config('client_catalogs');

        return view('clientes.editar', compact('cliente', 'campos', 'estadoSucursales', 'catalogs'));
    }

    public function update(Request $request, string $rfc)
    {
        $cliente = ClienteMaestro::where('rfc', $rfc)->firstOrFail();

        $request->validate([
            'razon_social' => 'required|string|max:255',
            'status'       => 'required|string|max:1',
            // RFC no se puede cambiar (es la llave de búsqueda)
        ]);

        $dataMaestro = $this->prepareData($request, $cliente->id_global);

        // El RFC nunca cambia en edición — preservar el valor original
        // (el campo es readonly en el form, el navegador no lo envía)
        $dataMaestro['rfc'] = $cliente->rfc;

        // 1. Actualizar maestro
        $cliente->update($dataMaestro);


        // 2. Actualizar en todas las sucursales activas
        $branches    = $this->connectionManager->getActiveBranches();
        $resultados  = [];
        $dataBranch  = $this->toBranchFormat($dataMaestro);

        foreach ($branches as $branch) {
            try {
                $conn = $this->connectionManager->connect($branch->code);

                // Buscar primero por IdGlobal
                $found = null;
                if ($cliente->id_global > 0) {
                    $found = $conn->table('clientes')
                        ->where('IdGlobal', $cliente->id_global)
                        ->first();
                }

                // Fallback: buscar por RFC
                if (!$found) {
                    $found = $conn->table('clientes')
                        ->where('RFC', $rfc)
                        ->first();
                }

                if ($found) {
                    // Filtrar solo columnas que existen en esta sucursal
                    $columnasReales = $this->getBranchColumns($conn, 'clientes');
                    $dataFiltrada   = array_filter(
                        $dataBranch,
                        fn($key) => in_array($key, $columnasReales),
                        ARRAY_FILTER_USE_KEY
                    );

                    // Mapeo específico para vendedores/asesores (por límite de caracteres del ERP)
                    $codigoVendedorAsesor = (strtoupper($branch->name) === 'TAPATIA') ? 'EITSA' : strtoupper($branch->name);

                    // Regla estricta para Asesor y Vendedor
                    if (in_array('Asesor', $columnasReales)) {
                        $dataFiltrada['Asesor'] = $codigoVendedorAsesor;
                    }
                    
                    if (in_array('Vendedor', $columnasReales)) {
                        // Si el maestro dice TOP, se respeta TOP. Si no, va el nombre de la sucursal.
                        $dataFiltrada['Vendedor'] = (strtoupper($dataMaestro['vendedor'] ?? '') === 'TOP') 
                                                  ? 'TOP' 
                                                  : $codigoVendedorAsesor;
                    }

                    $conn->table('clientes')
                        ->where('Cliente', $found->Cliente)
                        ->update($dataFiltrada);

                    $resultados[] = [
                        'sucursal' => $branch->name,
                        'status'   => 'ok',
                        'metodo'   => $cliente->id_global > 0 && $found->IdGlobal == $cliente->id_global
                            ? 'IdGlobal' : 'RFC',
                    ];
                } else {
                    $resultados[] = [
                        'sucursal' => $branch->name,
                        'status'   => 'not_found',
                        'message'  => 'Cliente no encontrado en esta sucursal.',
                    ];
                }
            } catch (Throwable $e) {
                $resultados[] = [
                    'sucursal' => $branch->name,
                    'status'   => 'error',
                    'message'  => $e->getMessage(),
                ];
            }
        }

        // Sync a PowerSales (no bloquea ni revierte si falla; ver storage/logs/powersales.log)
        $this->powerSales->syncCliente($dataBranch);

        $exitosos = count(array_filter($resultados, fn($r) => $r['status'] === 'ok'));
        $total    = count($resultados);

        // Construir detalle de errores para diagnóstico
        $errores = array_filter($resultados, fn($r) => $r['status'] === 'error');
        $detalleError = '';
        foreach ($errores as $err) {
            $detalleError .= " | [{$err['sucursal']}]: {$err['message']}";
        }

        return redirect()->route('clientes.edit', $rfc)
            ->with('success', "Cliente actualizado en {$exitosos}/{$total} sucursales." . ($detalleError ? " ERRORES:{$detalleError}" : ''))
            ->with('resultados', $resultados);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BLOQUEAR (destroy lógico)
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(string $rfc)
    {
        $cliente = ClienteMaestro::where('rfc', $rfc)->firstOrFail();

        // 1. Bloquear en maestro
        $cliente->update([
            'status'           => 'I',
            'id_opcion_bloqueo' => 1,
        ]);

        // 2. Bloquear en todas las sucursales
        $branches   = $this->connectionManager->getActiveBranches();
        $resultados = [];

        foreach ($branches as $branch) {
            try {
                $conn  = $this->connectionManager->connect($branch->code);
                $found = null;

                if ($cliente->id_global > 0) {
                    $found = $conn->table('clientes')->where('IdGlobal', $cliente->id_global)->first();
                }
                if (!$found) {
                    $found = $conn->table('clientes')->where('RFC', $rfc)->first();
                }

                if ($found) {
                    $conn->table('clientes')
                        ->where('Cliente', $found->Cliente)
                        ->update(['Status' => 'I', 'IdOpcionBloqueo' => 1]);

                    $resultados[] = ['sucursal' => $branch->name, 'status' => 'ok'];
                } else {
                    $resultados[] = ['sucursal' => $branch->name, 'status' => 'not_found'];
                }
            } catch (Throwable $e) {
                $resultados[] = ['sucursal' => $branch->name, 'status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return redirect()->route('clientes.index')
            ->with('success', "Cliente {$rfc} bloqueado exitosamente.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CAMPOS GLOBALES
    // ─────────────────────────────────────────────────────────────────────────

    public function campos(): View
    {
        $campos = ClienteCampo::orderBy('id')->get();
        return view('clientes.campos', compact('campos'));
    }

    public function updateCampos(Request $request)
    {
        $inCreate = $request->input('campos_create', []);
        $inEdit   = $request->input('campos_edit', []);

        DB::transaction(function () use ($inCreate, $inEdit) {
            // Desactivar todos los no requeridos
            ClienteCampo::where('is_required', false)->update(['show_in_create' => false, 'show_in_edit' => false]);
            
            // Activar los seleccionados en Crear
            if (!empty($inCreate)) {
                ClienteCampo::whereIn('campo', $inCreate)->update(['show_in_create' => true]);
            }

            // Activar los seleccionados en Editar
            if (!empty($inEdit)) {
                ClienteCampo::whereIn('campo', $inEdit)->update(['show_in_edit' => true]);
            }

            // Forzar requeridos siempre activos en ambos
            ClienteCampo::where('is_required', true)->update(['show_in_create' => true, 'show_in_edit' => true]);
        });

        return redirect()->route('clientes.campos')
            ->with('success', 'Configuración de campos actualizada correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CAMPOS DE SUCURSAL (configuración)
    // ─────────────────────────────────────────────────────────────────────────

    public function camposSucursal(): View
    {
        $campos = ClienteCampoSucursal::orderBy('orden')->orderBy('id')->get();
        return view('clientes.campos_sucursal', compact('campos'));
    }

    public function updateCamposSucursal(Request $request)
    {
        $request->validate([
            'campos'              => 'nullable|array',
            'campos.*.campo'      => 'required|string|max:60',
            'campos.*.label'      => 'required|string|max:100',
            'campos.*.tipo'       => 'required|in:text,number,decimal,date,boolean',
            'campos.*.orden'      => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $incomingCampos = collect($request->input('campos', []));
            $editableSet    = collect($request->input('editables', []));

            // Borrar todos y recrear (tabla pequeña, sin historial)
            // Nota: TRUNCATE hace commit implícito en MySQL — usar DELETE en su lugar
            ClienteCampoSucursal::query()->delete();

            foreach ($incomingCampos as $idx => $row) {
                ClienteCampoSucursal::create([
                    'campo'    => trim($row['campo']),
                    'label'    => trim($row['label']),
                    'tipo'     => $row['tipo'],
                    'editable' => $editableSet->contains(trim($row['campo'])),
                    'orden'    => (int) $row['orden'],
                ]);
            }
        });

        return redirect()->route('clientes.campos_sucursal')
            ->with('success', 'Configuración de campos de sucursal actualizada correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EDICIÓN POR SUCURSAL
    // ─────────────────────────────────────────────────────────────────────────

    public function editSucursal(string $rfc, int $branchId): View
    {
        // Verificar que el cliente exista en el maestro
        $cliente = ClienteMaestro::where('rfc', $rfc)->firstOrFail();

        // Obtener la sucursal por ID (evita problemas con códigos que contienen '/')
        $branch = Branch::findOrFail($branchId);

        // Conectar a la sucursal
        $conn = $this->connectionManager->connect($branch->code);

        // Buscar el cliente en la sucursal
        $registro = null;
        if ($cliente->id_global > 0) {
            $registro = $conn->table('clientes')->where('IdGlobal', $cliente->id_global)->first();
        }
        if (!$registro) {
            $registro = $conn->table('clientes')->where('RFC', $rfc)->first();
        }

        if (!$registro) {
            abort(404, "El cliente {$rfc} no existe en la sucursal {$branch->name}.");
        }

        // Obtener columnas reales de esta sucursal
        $columnasReales = $this->getBranchColumns($conn, 'clientes');

        // Obtener campos configurados que existan en esta sucursal
        $campos = ClienteCampoSucursal::orderBy('orden')->orderBy('id')->get()
            ->filter(fn($c) => in_array($c->campo, $columnasReales))
            ->values();

        return view('clientes.editar_sucursal', compact(
            'cliente', 'branch', 'registro', 'campos'
        ));
    }

    public function updateSucursal(Request $request, string $rfc, int $branchId)
    {
        $cliente = ClienteMaestro::where('rfc', $rfc)->firstOrFail();
        $branch  = Branch::findOrFail($branchId);

        // Conectar a la sucursal
        $conn = $this->connectionManager->connect($branch->code);

        // Encontrar el registro
        $found = null;
        if ($cliente->id_global > 0) {
            $found = $conn->table('clientes')->where('IdGlobal', $cliente->id_global)->first();
        }
        if (!$found) {
            $found = $conn->table('clientes')->where('RFC', $rfc)->first();
        }
        if (!$found) {
            return back()->withErrors(['No se encontró el cliente en la sucursal.']);
        }

        // Solo actualizar campos configurados como editables y que existan en la sucursal
        $columnasReales = $this->getBranchColumns($conn, 'clientes');
        $camposEditables = ClienteCampoSucursal::where('editable', true)
            ->orderBy('orden')
            ->get()
            ->filter(fn($c) => in_array($c->campo, $columnasReales));

        $dataUpdate = [];
        foreach ($camposEditables as $campo) {
            $key = $campo->campo;
            if ($request->has($key)) {
                $val = $request->input($key);
                $dataUpdate[$key] = match($campo->tipo) {
                    'number'  => (int) $val,
                    'decimal' => (float) $val,
                    'boolean' => (int) (bool) $val,
                    default   => (string) $val,
                };
            }
        }

        if (!empty($dataUpdate)) {
            $conn->table('clientes')
                ->where('Cliente', $found->Cliente)
                ->update($dataUpdate);
        }

        return redirect()
            ->route('clientes.edit_sucursal', ['rfc' => $rfc, 'branchId' => $branchId])
            ->with('success', "Campos actualizados correctamente en la sucursal {$branch->name}.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Estado por sucursal
    // ─────────────────────────────────────────────────────────────────────────

    public function estadoSucursales(string $rfc)
    {
        $cliente = ClienteMaestro::where('rfc', $rfc)->firstOrFail();
        $estado  = $this->resolveEstadoSucursales($cliente);
        return response()->json($estado);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HOMOLOGACION / SYNC
    // ─────────────────────────────────────────────────────────────────────────

    public function sync()
    {
        $statusFile = \App\Console\Commands\SyncClientesMaestro::statusFile();

        // Si ya hay una sincronización corriendo (< 10 min), rechazar
        if (file_exists($statusFile)) {
            $prev = json_decode(file_get_contents($statusFile), true);
            if (($prev['status'] ?? '') === 'running' && (time() - (int)($prev['updated_at'] ?? 0)) < 600) {
                return response()->json(['status' => 'already_running', 'message' => 'Ya hay una sincronización en progreso.']);
            }
        }

        // Escribir estado inicial
        file_put_contents($statusFile, json_encode([
            'status'     => 'running',
            'message'    => 'Preparando entorno...',
            'step'       => 0,
            'total'      => 0,
            'updated_at' => time(),
        ], JSON_UNESCAPED_UNICODE));

        // Lanzar proceso de fondo en Windows (start /B) sin bloquear a Apache
        $php     = PHP_BINARY;
        $artisan = base_path('artisan');
        $log     = storage_path('logs') . DIRECTORY_SEPARATOR . 'sync_clientes_bg.log';

        $cmd = 'start "" /B "' . $php . '" "' . $artisan . '" unidata:sync-clientes-maestro >> "' . $log . '" 2>&1';
        pclose(popen($cmd, 'r'));

        return response()->json(['status' => 'started']);
    }

    public function syncStatus()
    {
        $file = \App\Console\Commands\SyncClientesMaestro::statusFile();
        if (!file_exists($file)) {
            return response()->json(['status' => 'waiting', 'message' => 'Preparando...', 'step' => 0, 'total' => 0]);
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);

        return response()->json($data ?: ['status' => 'waiting', 'message' => 'Preparando...', 'step' => 0, 'total' => 0]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Prepara el array de datos en snake_case a partir del request (para maestro).
     */
    private function prepareData(Request $request, int $idGlobal): array
    {
        return [
            'id_global'         => $idGlobal,
            'rfc'               => strtoupper(trim($request->input('rfc', ''))),
            'razon_social'      => $request->input('razon_social', ''),
            'calle'             => $request->input('calle', ''),
            'exterior'          => $request->input('exterior', ''),
            'interior'          => $request->input('interior', ''),
            'colonia'           => $request->input('colonia', ''),
            'cod_postal'        => (int) $request->input('cod_postal', 0),
            'ciudad'            => $request->input('ciudad', ''),
            'municipio'         => $request->input('municipio', ''),
            'telefono1'         => $request->input('telefono1', ''),
            'telefono2'         => $request->input('telefono2', ''),
            'telefono3'         => $request->input('telefono3', ''),
            'fax'               => $request->input('fax', ''),
            'vendedor'          => $request->input('vendedor', ''),
            'documentos'        => $request->input('documentos', ''),
            'dias_pago'         => $request->input('dias_pago', ''),
            'dias_revision'     => $request->input('dias_revision', ''),
            'condicion_pago'    => $request->input('condicion_pago', ''),
            'dias_credito'      => (int) $request->input('dias_credito', 0),
            'limite_credito'    => (float) $request->input('limite_credito', 0),
            'otorgo_credito'    => $request->boolean('otorgo_credito') ? 1 : 0,
            'status'            => strtoupper($request->input('status', 'A')),
            'cta_contable'      => $request->input('cta_contable', ''),
            'fecha_alta'        => $request->input('fecha_alta') ?: now()->toDateString(),
            'representante'     => $request->input('representante', ''),
            'addenda'           => $request->input('addenda', ''),
            'id_reg_id_trib'    => $request->input('id_reg_id_trib', ''),
            'regimen_fiscal'    => $request->input('regimen_fiscal', ''),
            'anticipos'         => $request->input('anticipos', 'D'),
            'codigo_contpaq'    => (int) $request->input('codigo_contpaq', 0),
            'clasificacion'     => (int) $request->input('clasificacion', 0),
            'modificar_fpmpfac' => $request->boolean('modificar_fpmpfac') ? 1 : 0,
            'identificador'     => $request->input('identificador', ''),
            'id_opcion_bloqueo' => (int) $request->input('id_opcion_bloqueo', 0),
            'observaciones_caja'=> $request->input('observaciones_caja', ''),
            'sync'              => 0,
            'prefijo_descripcion' => 0,
            'id_sugar'          => $request->input('id_sugar', ''),
            'giro_principal'    => (int) $request->input('giro_principal', 0),
            'asesor'            => $request->input('asesor', ''),
            'ubicacion'         => $request->input('ubicacion', ''),
        ];
    }

    /**
     * Busca un cliente por RFC en las sucursales activas y lo importa al maestro.
     * Útil para clientes que existían antes de implementar este sistema.
     */
    private function importarDesdeRamas(string $rfc): ?ClienteMaestro
    {
        $branches = $this->connectionManager->getActiveBranches();

        foreach ($branches as $branch) {
            try {
                $conn = $this->connectionManager->connect($branch->code);
                $row  = $conn->table('clientes')->where('RFC', $rfc)->first();

                if (!$row) continue;

                // Calcular nuevo IdGlobal
                $maxGlobal  = (int)(ClienteMaestro::max('id_global') ?? 0);
                foreach ($branches as $b) {
                    try {
                        $c = $this->connectionManager->connect($b->code);
                        $maxGlobal = max($maxGlobal, (int)($c->table('clientes')->max('IdGlobal') ?? 0));
                    } catch (Throwable) {}
                }
                $nuevoIdGlobal = $maxGlobal + 1;

                // Mapear campos del branch al maestro (PascalCase → snake_case)
                return ClienteMaestro::create([
                    'id_global'          => (int)($row->IdGlobal ?? $nuevoIdGlobal),
                    'rfc'                => $rfc,
                    'razon_social'       => $row->Razon_Social ?? '',
                    'calle'              => $row->Calle ?? '',
                    'exterior'           => $row->Exterior ?? '',
                    'interior'           => $row->Interior ?? '',
                    'colonia'            => $row->Colonia ?? '',
                    'cod_postal'         => (int)($row->Cod_Postal ?? 0),
                    'ciudad'             => $row->Ciudad ?? '',
                    'municipio'          => $row->Municipio ?? '',
                    'telefono1'          => $row->Telefono1 ?? '',
                    'telefono2'          => $row->Telefono2 ?? '',
                    'telefono3'          => $row->Telefono3 ?? '',
                    'fax'                => $row->Fax ?? '',
                    'vendedor'           => $row->Vendedor ?? '',
                    'documentos'         => $row->Documentos ?? '',
                    'dias_pago'          => $row->Dias_Pago ?? '',
                    'dias_revision'      => $row->Dias_Revision ?? '',
                    'condicion_pago'     => $row->Condicion_Pago ?? '',
                    'dias_credito'       => (int)($row->Dias_Credito ?? 0),
                    'limite_credito'     => (float)($row->Limite_Credito ?? 0),
                    'otorgo_credito'     => (int)($row->OtorgoCreditO ?? 0),
                    'saldo_actual'       => (float)($row->Saldo_Actual ?? 0),
                    'status'             => $row->Status ?? 'A',
                    'cta_contable'       => $row->Cta_Contable ?? '',
                    'fecha_alta'         => $row->Fecha_Alta ?? now()->toDateString(),
                    'representante'      => $row->Representante ?? '',
                    'addenda'            => $row->Addenda ?? '',
                    'id_reg_id_trib'     => $row->IdRegIdTrib ?? '',
                    'regimen_fiscal'     => $row->RegimenFiscal ?? '',
                    'anticipos'          => $row->Anticipos ?? 'D',
                    'codigo_contpaq'     => (int)($row->CodigoContpaq ?? 0),
                    'clasificacion'      => (int)($row->Clasificacion ?? 0),
                    'modificar_fpmpfac'  => (int)($row->ModificarFPMPFac ?? 0),
                    'identificador'      => $row->Identificador ?? '',
                    'id_opcion_bloqueo'  => (int)($row->IdOpcionBloqueo ?? 0),
                    'observaciones_caja' => $row->ObservacionesCaja ?? '',
                    'sync'               => 0,
                    'prefijo_descripcion'=> 0,
                    'id_sugar'           => $row->IdSugar ?? '',
                    'giro_principal'     => (int)($row->GiroPrincipal ?? 0),
                    'asesor'             => $row->Asesor ?? '',
                    'ubicacion'          => $row->Ubicacion ?? '',
                ]);

            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Retorna las columnas reales de una tabla en una sucursal (con caché en memoria).
     */
    private array $columnCache = [];

    private function getBranchColumns($conn, string $table): array
    {
        $key = spl_object_id($conn) . '.' . $table;
        if (!isset($this->columnCache[$key])) {
            $cols = $conn->select("SHOW COLUMNS FROM `{$table}`");
            $this->columnCache[$key] = array_map(fn($c) => $c->Field, $cols);
        }
        return $this->columnCache[$key];
    }

    /**
     * Convierte de snake_case (maestro) a PascalCase (sucursal).
     */
    private function toBranchFormat(array $data): array
    {
        return [
            'IdGlobal'          => $data['id_global'] ?? 0,
            'RFC'               => $data['rfc'] ?? '',
            'Razon_Social'      => $data['razon_social'] ?? '',
            'Calle'             => $data['calle'] ?? '',
            'Exterior'          => $data['exterior'] ?? '',
            'Interior'          => $data['interior'] ?? '',
            'Colonia'           => $data['colonia'] ?? '',
            'Cod_Postal'        => $data['cod_postal'] ?? 0,
            'Ciudad'            => $data['ciudad'] ?? '',
            'Municipio'         => $data['municipio'] ?? '',
            'Telefono1'         => $data['telefono1'] ?? '',
            'Telfono2'          => $data['telefono2'] ?? '',   // typo real en la BD: "Telfono2"
            'Telefono3'         => $data['telefono3'] ?? '',
            'Fax'               => $data['fax'] ?? '',
            'Vendedor'          => $data['vendedor'] ?? '',
            'Documentos'        => $data['documentos'] ?? '',
            'Dias_Pago'         => $data['dias_pago'] ?? '',
            'Dias_Revision'     => $data['dias_revision'] ?? '',
            'Condicion_Pago'    => $data['condicion_pago'] ?? '',
            'Dias_Credito'      => $data['dias_credito'] ?? 0,
            'Limite_Credito'    => $data['limite_credito'] ?? 0,
            'OtorgoCreditO'     => $data['otorgo_credito'] ?? 0,
            'Saldo_Actual'      => $data['saldo_actual'] ?? 0,
            'Status'            => $data['status'] ?? 'A',
            'Cta_Contable'      => $data['cta_contable'] ?? '',
            'Fecha_Alta'        => $data['fecha_alta'] ?? now()->toDateString(),
            'Representante'     => $data['representante'] ?? '',
            'Addenda'           => $data['addenda'] ?? '',
            'IdRegIdTrib'       => $data['id_reg_id_trib'] ?? '',
            'RegimenFiscal'     => $data['regimen_fiscal'] ?? '',
            'Anticipos'         => $data['anticipos'] ?? 'D',
            'CodigoContpaq'     => $data['codigo_contpaq'] ?? 0,
            'Clasificacion'     => $data['clasificacion'] ?? 0,
            'ModificarFPMPFac'  => $data['modificar_fpmpfac'] ?? 0,
            'Identificador'     => $data['identificador'] ?? '',
            'IdOpcionBloqueo'   => $data['id_opcion_bloqueo'] ?? 0,
            'ObservacionesCaja' => $data['observaciones_caja'] ?? '',
            'Sync'              => $data['sync'] ?? 0,
            'PrefijoDescripcion'=> $data['prefijo_descripcion'] ?? 0,
            'IdSugar'           => $data['id_sugar'] ?? '',
            'GiroPrincipal'     => $data['giro_principal'] ?? 0,
            'Asesor'            => $data['asesor'] ?? '',
            'Ubicacion'         => $data['ubicacion'] ?? '',
        ];
    }

    /**
     * Consulta en qué sucursales existe el cliente y cómo fue localizado.
     */
    private function resolveEstadoSucursales(ClienteMaestro $cliente): array
    {
        $branches = $this->connectionManager->getActiveBranches();
        $estado   = [];

        foreach ($branches as $branch) {
            try {
                $conn  = $this->connectionManager->connect($branch->code);
                $found = null;
                $metodo = null;

                if ($cliente->id_global > 0) {
                    $found = $conn->table('clientes')
                        ->where('IdGlobal', $cliente->id_global)
                        ->first();
                    if ($found) $metodo = 'id_global';
                }

                if (!$found) {
                    $found = $conn->table('clientes')
                        ->where('RFC', $cliente->rfc)
                        ->first();
                    if ($found) $metodo = 'rfc';
                }

                $estado[] = [
                    'id'        => $branch->id,
                    'code'      => $branch->code,
                    'name'      => $branch->name,
                    'found'     => (bool) $found,
                    'metodo'    => $metodo,
                    'local_id'  => $found?->Cliente ?? null,
                    'id_global' => $found?->IdGlobal ?? null,
                    'error'     => null,
                ];
            } catch (Throwable $e) {
                $estado[] = [
                    'id'      => $branch->id,
                    'code'    => $branch->code,
                    'name'    => $branch->name,
                    'found'   => false,
                    'metodo'  => null,
                    'error'   => $e->getMessage(),
                ];
            }
        }

        return $estado;
    }
}
