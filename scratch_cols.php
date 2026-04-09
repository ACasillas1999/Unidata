<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$manager = app(\App\Services\BranchConnectionManager::class);
$branch = \App\Models\Branch::where('code', 'AIESA')->first();
$conn = $manager->connect($branch);
$cols = $conn->select('SHOW COLUMNS FROM articulo');
foreach ($cols as $col) {
    echo $col->Field . "\n";
}
