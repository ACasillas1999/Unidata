<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Rechazados count: " . \App\Models\ArticuloRechazado::count() . "\n";
echo "Pendientes count: " . \App\Models\ArticuloSinConfigurar::count() . "\n";
