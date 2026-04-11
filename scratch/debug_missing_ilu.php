<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Podemos probar con el que estaba en el otro script o uno que el usuario nos pase.
// Por ahora usemos el de ejemplo si no tenemos otro.
$clave = '10207RCVE211'; 

echo "Investigando Clave: [$clave]\n";

$row = DB::connection('ilu')->table('articulo')->where('Clave_Articulo', 'LIKE', "%$clave%")->first();

if ($row) {
    echo "Encontrado en ILU:\n";
    echo " - Clave exacto: [" . $row->Clave_Articulo . "]\n";
    echo " - Longitud: " . strlen($row->Clave_Articulo) . "\n";
    echo " - Habilitado: " . var_export($row->Habilitado, true) . "\n";
    echo " - Tipo de dato Habilitado: " . gettype($row->Habilitado) . "\n";
} else {
    echo "NO encontrado en ILU con LIKE %$clave%\n";
}

$matrix = DB::table('matriz_homologacions')->where('clave', $clave)->first();
if ($matrix) {
    echo "\nEn Matriz Local:\n";
    echo " - en_ilu: " . var_export($matrix->en_ilu, true) . "\n";
} else {
    echo "\nNo existe en la matriz local.\n";
}
