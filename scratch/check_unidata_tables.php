<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = ['csv_historial', 'csv_historial_detalles'];
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "Table '$table' exists in default (unidata) database.\n";
        print_r(Schema::getColumnListing($table));
    } else {
        echo "Table '$table' DOES NOT exist in default (unidata) database.\n";
    }
}
