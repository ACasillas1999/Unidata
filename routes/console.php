<?php

use App\Services\BranchConnectionManager;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('branches:status', function (BranchConnectionManager $manager) {
    $branches = $manager->getActiveBranches();

    if ($branches->isEmpty()) {
        $this->warn('No hay sucursales activas configuradas.');

        return self::SUCCESS;
    }

    $this->table(
        ['ID', 'Codigo', 'Nombre', 'Estado', 'Conexion', 'Ultima revision'],
        $branches->map(fn ($branch) => [
            'id' => $branch->id,
            'code' => $branch->code,
            'name' => $branch->name,
            'status' => $branch->status,
            'connection_status' => $branch->connection_status,
            'last_connection_check' => $branch->last_connection_check?->toDateTimeString() ?? 'N/A',
        ])->all(),
    );

    return self::SUCCESS;
})->purpose('Muestra las sucursales configuradas en la base local');

Artisan::command('branches:test {branch : ID o codigo de la sucursal}', function (BranchConnectionManager $manager, string $branch) {
    $resolvedBranch = $manager->findBranchOrFail($branch);

    if (! $manager->testConnection($resolvedBranch)) {
        $this->error("No se pudo abrir la conexion para {$resolvedBranch->code}.");

        return self::FAILURE;
    }

    $this->info("Conexion exitosa para {$resolvedBranch->code} ({$resolvedBranch->name}).");

    return self::SUCCESS;
})->purpose('Prueba la conexion dinamica de una sucursal');
