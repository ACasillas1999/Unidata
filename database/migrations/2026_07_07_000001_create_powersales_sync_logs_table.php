<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('powersales_sync_logs')) {
            return;
        }
        Schema::create('powersales_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 50);           // articulo | cliente
            $table->string('endpoint', 100);         // /products | /customers
            $table->string('referencia', 100);        // Clave_Articulo o RFC/IdGlobal
            $table->json('payload')->nullable();
            $table->boolean('success')->default(false);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity', 'referencia']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('powersales_sync_logs');
    }
};
