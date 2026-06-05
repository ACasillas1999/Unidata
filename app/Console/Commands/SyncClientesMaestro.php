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
    protected $description = 'Homologa clientes desde las sucursales hacia el maestro, importando los que tengan IdGlobal > 0 y no existan en el maestro.';

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

        $totalBranches = $branches->count();
        $this->writeStatus('running', 'Iniciando homologación...', 0, $totalBranches);
        $this->info("Iniciando homologación de clientes ({$totalBranches} sucursales activas)...");

        // Obtener los RFCs que ya existen en el maestro para omitirlos
        $rfcsExistentes = ClienteMaestro::pluck('rfc')->map(fn($r) => strtoupper(trim($r)))->toArray();
        $rfcsExistentes = array_flip($rfcsExistentes); // Para búsqueda rápida (O(1))

        $nuevosImportados = 0;
        $step = 0;

        foreach ($branches as $branch) {
            $step++;
            $this->writeStatus('running', "Analizando sucursal {$branch->name}...", $step, $totalBranches);
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

                            // Marcar como procesado para no volverlo a meter ni siquiera en la misma iteración
                            $rfcsExistentes[$rfc] = true;

                            // Mapear datos
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
                            // Insertar en bloque para mayor velocidad
                            ClienteMaestro::insert($nuevosParaInsertar);
                            $nuevosImportados += count($nuevosParaInsertar);
                        }
                    });

            } catch (Throwable $e) {
                $this->error("Error en {$branch->name}: " . $e->getMessage());
                continue;
            }
        }

        $this->writeStatus('done', "¡Sincronización completada! {$nuevosImportados} clientes nuevos homologados.", $totalBranches, $totalBranches);
        $this->info("¡Homologación Finalizada! {$nuevosImportados} clientes agregados al maestro.");
    }
}
