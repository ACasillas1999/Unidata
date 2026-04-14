<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BranchConnectionManager;
use Illuminate\Support\Facades\DB;

$manager = app(BranchConnectionManager::class);
$branches = \App\Models\Branch::where('status', 'active')->get();

if ($branches->isEmpty()) {
    echo "No active branches found.\n";
    exit;
}

$branch = $branches->first();
echo "Checking columns for branch: {$branch->name} ({$branch->code})\n";

try {
    $conn = $manager->connect($branch->code);
    $firstRow = $conn->table('articulo')->first();
    
    if ($firstRow) {
        $columns = array_keys((array)$firstRow);
        echo "Columns found in 'articulo' table:\n";
        foreach ($columns as $col) {
            echo "- $col\n";
        }
    } else {
        echo "No rows found in 'articulo' table to detect columns.\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
