<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use Illuminate\Support\Facades\Schema;

$branches = Branch::all()->pluck('name', 'code')->toArray();
$columns = Schema::getColumnListing('matriz_homologacions');

echo "--- BRANCHES IN DB ---\n";
print_r($branches);

echo "\n--- COLUMNS IN TABLE ---\n";
print_r($columns);
