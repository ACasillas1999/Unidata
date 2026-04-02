<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchesSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['code' => 'DEASA',       'name' => 'DEASA',       'db_host' => '192.168.20.1',  'db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'deasa',     'status' => 'active', 'connection_status' => 'pending'],
            ['code' => 'AIESA',       'name' => 'AIESA',       'db_host' => '192.168.40.1',  'db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'aiesa',     'status' => 'active', 'connection_status' => 'pending'],
            ['code' => 'SEGSA',       'name' => 'SEGSA',       'db_host' => '192.168.30.1',  'db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'segsa',     'status' => 'active', 'connection_status' => 'pending'],
            ['code' => 'FESA',        'name' => 'FESA',        'db_host' => '192.168.50.1',  'db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'fesa',      'status' => 'active', 'connection_status' => 'pending'],
            ['code' => 'TAPATIA',     'name' => 'TAPATIA',     'db_host' => '192.168.70.1',  'db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'tapatia',   'status' => 'active', 'connection_status' => 'pending'],
            ['code' => 'DIMEGSA',     'name' => 'DIMEGSA',     'db_host' => '192.168.10.1',  'db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'dimegsa',   'status' => 'active', 'connection_status' => 'pending'],
            ['code' => 'GABSA',       'name' => 'GABSA',       'db_host' => '192.168.1.1',   'db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'gabsa',     'status' => 'active', 'connection_status' => 'pending'],
            ['code' => 'VALLARTA',    'name' => 'VALLARTA',    'db_host' => '192.168.120.1', 'db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'vallarta',  'status' => 'active', 'connection_status' => 'pending'],
            ['code' => 'QUERETARO',   'name' => 'QUERETARO',   'db_host' => '192.168.140.1', 'db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'queretaro', 'status' => 'active', 'connection_status' => 'pending'],
            ['code' => 'ILUMINACION', 'name' => 'ILUMINACION', 'db_host' => '192.168.2.1',   'db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'ilu',       'status' => 'active', 'connection_status' => 'pending'],
            ['code' => 'CODI',        'name' => 'CODI',        'db_host' => '192.168.150.1', 'db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'washington','status' => 'active', 'connection_status' => 'pending'],
            ['code' => 'CEDIS',       'name' => 'CEDIS',       'db_host' => '192.168.100.20','db_port' => 3307, 'db_user' => 'consulta', 'db_password' => 'ctl3026', 'db_database' => 'cedis',     'status' => 'active', 'connection_status' => 'pending'],
        ];

        $now = now();

        foreach ($branches as $branch) {
            DB::table('branches')->updateOrInsert(
                ['code' => $branch['code']],
                array_merge($branch, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $this->command->info('✅ ' . count($branches) . ' sucursales insertadas/actualizadas correctamente.');
    }
}
