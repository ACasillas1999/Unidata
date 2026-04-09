<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Recreando tabla matriz_homologacions...\n";

try {
    // 1. Limpiar registros viejos de la tabla de migraciones para que Laravel permita ejecutarlas de nuevo
    DB::table('migrations')
        ->where('migration', 'LIKE', '%create_matriz_homologacions_table%')
        ->orWhere('migration', 'LIKE', '%add_extra_columns_to_matriz_homologacions_table%')
        ->delete();

    echo "Registros de migración limpiados.\n";

    // 2. Ejecutar las migraciones
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    
    echo "Artisan migrate ejecutado:\n";
    echo \Illuminate\Support\Facades\Artisan::output();

    echo "\n¡Proceso finalizado! La tabla debería estar de vuelta.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
