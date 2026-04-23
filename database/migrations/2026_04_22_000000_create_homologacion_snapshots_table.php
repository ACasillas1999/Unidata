<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homologacion_snapshots', function (Blueprint $table) {
            $table->id();
            $table->dateTime('synced_at')->comment('Timestamp de la sincronización que generó el snapshot');
            $table->string('branch_code', 50)->comment('Código de sucursal (ej: deasa, aiesa)');
            $table->string('branch_name', 100)->comment('Nombre legible de la sucursal');
            $table->unsignedInteger('total_activos')->default(0)->comment('Artículos con en_{branch} = 1');
            $table->unsignedInteger('total_inactivos')->default(0)->comment('Artículos con en_{branch} = 0');
            $table->unsignedInteger('total_falta')->default(0)->comment('Artículos con en_{branch} = NULL');
            $table->timestamps();

            $table->index('synced_at');
            $table->index('branch_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homologacion_snapshots');
    }
};
