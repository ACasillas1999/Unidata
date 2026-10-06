<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CLIENTES MAESTRO ===\n";
$clientes = \App\Models\ClienteMaestro::where('razon_social', 'LIKE', '%CASILLAS%')
    ->orWhere('rfc', 'LIKE', '%CASILLAS%')
    ->get();

foreach ($clientes as $c) {
    echo "ID Global: {$c->id_global} | RFC: {$c->rfc} | Razon Social: {$c->razon_social} | Status: {$c->status} | Created: {$c->created_at}\n";
}

echo "\n=== POWERSALES SYNC LOGS (DB) ===\n";
$logs = \Illuminate\Support\Facades\DB::table('powersales_sync_logs')
    ->orderByDesc('created_at')
    ->limit(10)
    ->get();

foreach ($logs as $l) {
    echo "ID: {$l->id} | Entity: {$l->entity} | Ref: {$l->referencia} | Status: {$l->status_code} | Success: {$l->success} | Date: {$l->created_at}\n";
}
