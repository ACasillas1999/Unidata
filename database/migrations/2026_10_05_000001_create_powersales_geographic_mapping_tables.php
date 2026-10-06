<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('powersales_mapping_estados', function (Blueprint $table) {
            $table->id();
            $table->string('magic_clave', 20)->unique();
            $table->string('magic_descripcion', 150);
            $table->unsignedBigInteger('ps_state_id')->nullable();
            $table->string('ps_state_name', 150)->nullable();
            $table->string('ps_state_number', 20)->nullable();
            $table->string('ps_states_col', 20)->nullable();
            $table->timestamps();

            $table->index('ps_state_id');
        });

        Schema::create('powersales_mapping_ciudades', function (Blueprint $table) {
            $table->id();
            $table->string('magic_cve_ciudad', 20)->unique();
            $table->string('magic_dsc_ciudad', 150);
            $table->string('magic_cve_estado', 20)->index();
            $table->string('magic_cve_pais', 20)->default('MEX');
            $table->unsignedBigInteger('ps_city_id')->nullable();
            $table->string('ps_city_name', 150)->nullable();
            $table->string('ps_city_number', 20)->nullable();
            $table->unsignedBigInteger('ps_state_id')->nullable();
            $table->timestamps();

            $table->index('ps_city_id');
            $table->index(['magic_cve_estado', 'ps_city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('powersales_mapping_ciudades');
        Schema::dropIfExists('powersales_mapping_estados');
    }
};
