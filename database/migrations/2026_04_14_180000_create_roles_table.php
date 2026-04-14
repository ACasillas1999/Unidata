<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();          // "Administrador"
            $table->string('slug')->unique();          // "administrador"
            $table->string('description')->nullable(); // Descripción libre
            $table->string('color', 7)->default('#6366f1'); // Hex para badge UI
            $table->boolean('is_system')->default(false);   // Protege contra eliminación
            $table->json('permissions');               // Estructura granular de permisos
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
