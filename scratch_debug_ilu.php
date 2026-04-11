<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$clave = '10207RCVE211';
$result = DB::connection('ilu')->table('articulo')->where('Clave_Articulo', 'LIKE', "%$clave%")->get();

echo "Resultados para ILU:\n";
foreach ($result as $row) {
    echo "Clave: [" . $row->Clave_Articulo . "] - Longitud: " . strlen($row->Clave_Articulo) . "\n";
}

$matrix = DB::table('matriz_homologacions')->where('clave', $clave)->first();
echo "\nEn Matriz:\n";
if ($matrix) {
    echo "Clave: [" . $matrix->clave . "] - en_ilu: " . var_export($matrix->en_ilu, true) . "\n";
} else {
    echo "No existe en la matriz local.\n";
}
