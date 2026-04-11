<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$columns = [];
try {
    $columns = Schema::connection('db_master')->getColumnListing('csv_historial');
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "Columns in db_master.csv_historial:\n";
print_r($columns);
