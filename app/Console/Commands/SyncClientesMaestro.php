<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\ClienteMaestro;
use App\Services\BranchConnectionManager;
use Illuminate\Console\Command;
use Throwable;

class SyncClientesMaestro extends Command
{
    protected $signature   = 'unidata:sync-clientes-maestro';
    protected $description = 'Homologa clientes desde las sucursales hacia el maestro, importando los que tengan IdGlobal > 0 y luego empujando la info a las sucursales.';

    /** Ruta del archivo de estado compartido con el controller */
    public static function statusFile(): string
    {
        return storage_path('app/sync_clientes_status.json');
    }

    private function writeStatus(string $status, string $message, int $step = 0, int $total = 0): void
    {
        $json = json_encode([
            'status'     => $status,        // running | done | error
            'message'    => $message,
            'step'       => $step,
            'total'      => $total,
            'updated_at' => time(),
        ], JSON_UNESCAPED_UNICODE);
        file_put_contents(self::statusFile(), $json, LOCK_EX);
    }

    public function handle(BranchConnectionManager $manager)
    {
        $branches = Branch::query()->active()->orderBy('name')->get();

        if ($branches->isEmpty()) {
            $this->writeStatus('done', 'No hay sucursales activas configuradas.', 0, 0);
            $this->warn('No hay sucursales activas. Nada que sincronizar.');
            return;
        }

        // FASE 1: IMPORTAR AL MAESTRO (Escaneo)
        $totalBranches = $branches->count();
        $this->writeStatus('running', 'Fase 1/2: Escaneando clientes...', 0, $totalBranches * 2);
        $this->info("Fase 1: Iniciando homologación de clientes ({$totalBranches} sucursales activas)...");

        // Obtener los RFCs que ya existen en el maestro para omitirlos
        $rfcsExistentes = ClienteMaestro::pluck('rfc')->map(fn($r) => strtoupper(trim($r)))->toArray();
        $rfcsExistentes = array_flip($rfcsExistentes); // Para búsqueda rápida (O(1))

        $nuevosImportados = 0;
        $step = 0;

        foreach ($branches as $branch) {
            $step++;
            $this->writeStatus('running', "Fase 1: Escaneando {$branch->name}...", $step, $totalBranches * 2);
            $this->info("Conectando a [{$branch->name}]...");

            try {
                $conn = $manager->connect($branch->code);
                
                // Traer todos los clientes de la sucursal que tengan IdGlobal > 0
                // Usamos chunk para no saturar memoria
                $conn->table('clientes')
                    ->where('IdGlobal', '>', 0)
                    ->whereNotNull('RFC')
                    ->where('RFC', '!=', '')
                    ->orderBy('RFC')
                    ->chunk(1000, function ($clientes) use (&$rfcsExistentes, &$nuevosImportados) {
                        
                        $nuevosParaInsertar = [];

                        foreach ($clientes as $row) {
                            $rfc = strtoupper(trim($row->RFC));

                            if ($rfc === '' || isset($rfcsExistentes[$rfc])) {
                                continue;
                            }

                            // Marcar como procesado para no volverlo a meter
                            $rfcsExistentes[$rfc] = true;

                            // Mapear datos al formato Maestro
                            $nuevosParaInsertar[] = [
                                'id_global'          => (int)($row->IdGlobal ?? 0),
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
                                'created_at'         => now(),
                                'updated_at'         => now(),
                            ];
                        }

                        if (!empty($nuevosParaInsertar)) {
                            ClienteMaestro::insert($nuevosParaInsertar);
                            $nuevosImportados += count($nuevosParaInsertar);
                        }
                    });

            } catch (Throwable $e) {
                $this->error("Error en {$branch->name}: " . $e->getMessage());
                continue;
            }
        }

        // FASE 2: EMPUJAR DATOS A SUCURSALES (Push down)
        $this->info("Fase 2: Empujando datos maestros a sucursales...");

        foreach ($branches as $branch) {
            $step++;
            $this->writeStatus('running', "Fase 2: Empujando a {$branch->name}...", $step, $totalBranches * 2);
            
            try {
                $conn = $manager->connect($branch->code);
                
                // Obtener columnas reales de esta sucursal
                $cols = $conn->select("SHOW COLUMNS FROM `clientes`");
                $columnasReales = array_map(fn($c) => $c->Field, $cols);

                // Procesamos en chunks para no bloquear memoria
                $conn->table('clientes')
                    ->where('IdGlobal', '>', 0)
                    ->orderBy('Cliente')
                    ->chunk(500, function ($clientesSucursal) use ($conn, $branch, $columnasReales) {
                        
                        // Extraer RFCs de este chunk
                        $rfcs = $clientesSucursal->pluck('RFC')->filter()->unique()->toArray();
                        if (empty($rfcs)) return;

                        // Buscar esos RFCs en el maestro
                        $maestros = ClienteMaestro::whereIn('rfc', $rfcs)->get()->keyBy('rfc');

                        foreach ($clientesSucursal as $row) {
                            $rfc = $row->RFC;
                            if (!isset($maestros[$rfc])) continue;

                            $dataMaestro = $maestros[$rfc];

                            // Convertir a formato Sucursal
                            $dataBranch = $this->toBranchFormat($dataMaestro);

                            // Filtrar solo las columnas que existen
                            $dataFiltrada = array_filter(
                                $dataBranch,
                                fn($key) => in_array($key, $columnasReales),
                                ARRAY_FILTER_USE_KEY
                            );

                            // Mapeo específico para vendedores/asesores (por límite de caracteres del ERP)
                            $codigoVendedorAsesor = (strtoupper($branch->name) === 'TAPATIA') ? 'EITSA' : strtoupper($branch->name);

                            // Regla estricta para Asesor y Vendedor (igual que en ClientesController)
                            if (in_array('Asesor', $columnasReales)) {
                                $dataFiltrada['Asesor'] = $codigoVendedorAsesor;
                            }
                            
                            if (in_array('Vendedor', $columnasReales)) {
                                $dataFiltrada['Vendedor'] = (strtoupper($dataMaestro->vendedor ?? '') === 'TOP') 
                                                          ? 'TOP' 
                                                          : $codigoVendedorAsesor;
                            }

                            // Actualizar en la sucursal
                            $conn->table('clientes')
                                 ->where('Cliente', $row->Cliente)
                                 ->update($dataFiltrada);
                        }
                    });

            } catch (Throwable $e) {
                $this->error("Error empujando a {$branch->name}: " . $e->getMessage());
                continue;
            }
        }

        $this->writeStatus('done', "¡Homologación Bidireccional completada! {$nuevosImportados} importados.", $totalBranches * 2, $totalBranches * 2);
        $this->info("¡Finalizado! {$nuevosImportados} agregados y empujados a sucursales.");
    }

    /**
     * Convierte de snake_case (maestro) a PascalCase (sucursal).
     */
    private function toBranchFormat($data): array
    {
        return [
            'IdGlobal'          => $data->id_global,
            'RFC'               => $data->rfc,
            'Razon_Social'      => $data->razon_social,
            'Calle'             => $data->calle,
            'Exterior'          => $data->exterior,
            'Interior'          => $data->interior,
            'Colonia'           => $data->colonia,
            'Cod_Postal'        => $data->cod_postal,
            'Ciudad'            => $data->ciudad,
            'Municipio'         => $data->municipio,
            'Telefono1'         => $data->telefono1,
            'Telefono2'         => $data->telefono2,
            'Telefono3'         => $data->telefono3,
            'Fax'               => $data->fax,
            'Vendedor'          => $data->vendedor,
            'Documentos'        => $data->documentos,
            'Dias_Pago'         => $data->dias_pago,
            'Dias_Revision'     => $data->dias_revision,
            'Condicion_Pago'    => $data->condicion_pago,
            'Dias_Credito'      => $data->dias_credito,
            'Limite_Credito'    => $data->limite_credito,
            'OtorgoCreditO'     => $data->otorgo_credito,
            'Saldo_Actual'      => $data->saldo_actual,
            'Status'            => $data->status,
            'Cta_Contable'      => $data->cta_contable,
            'Fecha_Alta'        => $data->fecha_alta,
            'Representante'     => $data->representante,
            'Addenda'           => $data->addenda,
            'IdRegIdTrib'       => $data->id_reg_id_trib,
            'RegimenFiscal'     => $data->regimen_fiscal,
            'Anticipos'         => $data->anticipos,
            'CodigoContpaq'     => $data->codigo_contpaq,
            'Clasificacion'     => $data->clasificacion,
            'ModificarFPMPFac'  => $data->modificar_fpmpfac,
            'Identificador'     => $data->identificador,
            'IdOpcionBloqueo'   => $data->id_opcion_bloqueo,
            'ObservacionesCaja' => $data->observaciones_caja,
            'IdSugar'           => $data->id_sugar,
            'GiroPrincipal'     => $data->giro_principal,
            'Asesor'            => $data->asesor,
            'Ubicacion'         => $data->ubicacion,
        ];
    }
}
