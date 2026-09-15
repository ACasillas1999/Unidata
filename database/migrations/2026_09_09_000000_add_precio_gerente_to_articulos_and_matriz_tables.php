<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar a db_master.Articulos si no existe
        if (Schema::connection('db_master')->hasTable('Articulos')) {
            Schema::connection('db_master')->table('Articulos', function (Blueprint $table) {
                if (!Schema::connection('db_master')->hasColumn('Articulos', 'precio_gerente')) {
                    $table->decimal('precio_gerente', 15, 4)->nullable()->after('desc_proveedor');
                }
            });
        }

        // 2. Agregar a matriz_homologacions (conexión default) si no existe
        if (Schema::hasTable('matriz_homologacions')) {
            Schema::table('matriz_homologacions', function (Blueprint $table) {
                if (!Schema::hasColumn('matriz_homologacions', 'precio_gerente')) {
                    $table->decimal('precio_gerente', 15, 4)->nullable()->after('desc_proveedor');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('db_master')->hasTable('Articulos')) {
            Schema::connection('db_master')->table('Articulos', function (Blueprint $table) {
                if (Schema::connection('db_master')->hasColumn('Articulos', 'precio_gerente')) {
                    $table->dropColumn('precio_gerente');
                }
            });
        }

        if (Schema::hasTable('matriz_homologacions')) {
            Schema::table('matriz_homologacions', function (Blueprint $table) {
                if (Schema::hasColumn('matriz_homologacions', 'precio_gerente')) {
                    $table->dropColumn('precio_gerente');
                }
            });
        }
    }
};
