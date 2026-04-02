<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MatrizHomologacion;

class SyncMatrizHomologacion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unidata:sync-matriz';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza todas las bases de datos de sucursales hacia la Matriz Maestra de Homologación de forma transversal (Upsert)';

    // Las mismas conexiones definidas en HomologacionController
    const BRANCHES = [
        'DEASA' => ['conn' => 'deasa', 'col' => 'en_deasa'],
        'AIESA' => ['conn' => 'aiesa', 'col' => 'en_aiesa'],
        'CEDIS' => ['conn' => 'cedis', 'col' => 'en_cedis'],
        'DIMEGSA' => ['conn' => 'dimegsa', 'col' => 'en_dimegsa'],
        'FESA' => ['conn' => 'fesa', 'col' => 'en_fesa'],
        'GABSA' => ['conn' => 'gabsa', 'col' => 'en_gabsa'],
        'ILU' => ['conn' => 'ilu', 'col' => 'en_ilu'],
        'QUERÉTARO' => ['conn' => 'queretaro', 'col' => 'en_queretaro'],
        'SEGSA' => ['conn' => 'segsa', 'col' => 'en_segsa'],
        'TAPATÍA' => ['conn' => 'tapatia', 'col' => 'en_tapatia'],
        'VALLARTA' => ['conn' => 'vallarta', 'col' => 'en_vallarta'],
        'WASHINGTON' => ['conn' => 'washington', 'col' => 'en_washington'],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando Sincronización de Matriz de Homologación...");
        
        // Opcional: Trucar la tabla si queremos un reinicio completo (o simplemente usar upsert para evitar esto)
        // Por seguridad, usaremos upsert y resetearemos todas las variables booleanas a 0 primero
        $this->info("Paso 1: Limpiando banderas de conexión anteriores...");
        MatrizHomologacion::query()->update([
            'en_deasa' => false,
            'en_aiesa' => false,
            'en_cedis' => false,
            'en_dimegsa' => false,
            'en_fesa' => false,
            'en_gabsa' => false,
            'en_ilu' => false,
            'en_queretaro' => false,
            'en_segsa' => false,
            'en_tapatia' => false,
            'en_vallarta' => false,
            'en_washington' => false,
        ]);

        foreach (self::BRANCHES as $branchName => $info) {
            $connName = strtolower($info['conn']);
            $colName = $info['col'];

            $this->info("-> Conectando a [{$branchName}]...");

            try {
                $query = DB::connection($connName)->table('articulo')
                    ->select('Clave_Articulo', 'Descripcion', 'Habilitado');

                // Ahora para todas las sucursales traemos el catálogo completo
                // ya no filtramos por activos, para tener la radiografía completa.

                $query->orderBy('Clave_Articulo')
                    ->chunk(2000, function ($articles) use ($colName, &$totalProcessed, $branchName) {
                        $upsertData = [];

                        foreach ($articles as $art) {
                            $upsertData[] = [
                                'clave' => $art->Clave_Articulo,
                                'descripcion' => $art->Descripcion ?: 'SIN DESCRIPCIÓN',
                                $colName => $art->Habilitado ? 1 : 0
                            ];
                        }

                        // upsert(valores, claves_unicas, columnas_a_actualizar_si_existe)
                        // Si ya existe, actualizamos SOLAMENTE su bandera ($colName), conservando la descripción original.
                        // Si NO existe, se inserta la clave, la descripción que trae, y su bandera.
                        MatrizHomologacion::upsert(
                            $upsertData,
                            ['clave'],
                            [$colName]
                        );

                        $totalProcessed += count($articles);
                        $this->output->write("\r   Procesados: {$totalProcessed} registros...");
                    });

                $this->info("\n   [OK] {$branchName} finalizado. Total agregados/actualizados: {$totalProcessed}");

            } catch (\Exception $e) {
                $this->error("\n   [ERROR] Falló al procesar {$branchName}: " . $e->getMessage());
            }
        }

        $this->info("¡Sincronización Transversal Finalizada con Éxito!");
    }
}
