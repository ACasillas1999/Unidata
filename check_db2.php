<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dbs = [];
try {
    $dbs = DB::connection('mysql')->select('SHOW DATABASES');
} catch (\Exception $e) {
    echo "Error connecting to mysql: " . $e->getMessage() . "\n";
}

echo "Databases on 'mysql' connection:\n";
foreach ($dbs as $db) {
    echo "- " . $db->Database . "\n";
}
