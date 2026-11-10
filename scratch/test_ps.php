<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $res = \Illuminate\Support\Facades\Http::timeout(5)->get('https://api.qa.powersales.cloud');
    echo 'Status: ' . $res->status() . "\n";
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}

