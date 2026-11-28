<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'db_master';

    public function up(): void
    {
        if (Schema::connection('db_master')->hasTable('Historial') || Schema::connection('db_master')->hasTable('historial')) {
            return;
        }
        Schema::connection('db_master')->create('Historial', function (Blueprint $table) {
            $table->id();
            $table->integer('total_articulos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('db_master')->dropIfExists('Historial');
    }
};
