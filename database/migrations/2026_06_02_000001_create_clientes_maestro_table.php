<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'db_master';

    public function up(): void
    {
        Schema::connection('db_master')->create('clientes_maestro', function (Blueprint $table) {
            $table->id();
            $table->integer('id_global')->default(0)->index();
            $table->string('rfc', 14)->unique();
            $table->string('razon_social', 255);
            $table->string('calle', 70)->nullable()->default('');
            $table->string('exterior', 10)->nullable()->default('');
            $table->string('interior', 10)->nullable()->default('');
            $table->string('colonia', 60)->nullable()->default('');
            $table->integer('cod_postal')->nullable()->default(0);
            $table->string('ciudad', 4)->nullable()->default('');
            $table->string('municipio', 30)->nullable()->default('');
            $table->string('telefono1', 15)->nullable()->default('');
            $table->string('telefono2', 15)->nullable()->default('');
            $table->string('telefono3', 15)->nullable()->default('');
            $table->string('fax', 15)->nullable()->default('');
            $table->string('vendedor', 6)->nullable()->default('');
            $table->string('documentos', 255)->nullable()->default('');
            $table->string('dias_pago', 6)->nullable()->default('');
            $table->string('dias_revision', 6)->nullable()->default('');
            $table->string('condicion_pago', 4)->nullable()->default('');
            $table->smallInteger('dias_credito')->nullable()->default(0);
            $table->double('limite_credito')->nullable()->default(0);
            $table->tinyInteger('otorgo_credito')->nullable()->default(0);
            $table->double('saldo_actual')->nullable()->default(0);
            $table->char('status', 1)->nullable()->default('A');
            $table->string('cta_contable', 14)->nullable()->default('');
            $table->date('fecha_alta')->nullable();
            $table->string('representante', 60)->nullable()->default('');
            $table->string('addenda', 15)->nullable()->default('');
            $table->string('id_reg_id_trib', 40)->nullable()->default('');
            $table->string('regimen_fiscal', 3)->nullable()->default('');
            $table->char('anticipos', 1)->nullable()->default('D');
            $table->integer('codigo_contpaq')->nullable()->default(0);
            $table->smallInteger('clasificacion')->nullable()->default(0);
            $table->tinyInteger('modificar_fpmpfac')->nullable()->default(0);
            $table->string('identificador', 10)->nullable()->default('');
            $table->tinyInteger('id_opcion_bloqueo')->nullable()->default(0);
            $table->string('observaciones_caja', 255)->nullable()->default('');
            $table->tinyInteger('sync')->nullable()->default(0)->index();
            $table->tinyInteger('prefijo_descripcion')->nullable()->default(0);
            $table->string('id_sugar', 36)->nullable()->default('');
            $table->tinyInteger('giro_principal')->nullable()->default(0);
            $table->string('asesor', 6)->nullable()->default('');
            $table->string('ubicacion', 3)->nullable()->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('db_master')->dropIfExists('clientes_maestro');
    }
};
