<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = \Illuminate\Support\Facades\DB::connection('db_master')
    ->table('Articulos')
    ->select('mn_usd')
    ->distinct()
    ->get();

print_r($rows);
