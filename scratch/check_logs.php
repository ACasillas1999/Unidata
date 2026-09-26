<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logs = \Illuminate\Support\Facades\DB::connection('mysql')
    ->table('powersales_sync_logs')
    ->where('referencia', '10200221504')
    ->orderBy('id', 'desc')
    ->take(10)
    ->get();

print_r($logs->toArray());
