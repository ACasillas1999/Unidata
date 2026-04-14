<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL no permite modificar un ENUM directamente a VARCHAR via Blueprint
        // en algunas versiones, así que usamos raw SQL para mayor compatibilidad.
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(64) NOT NULL DEFAULT 'Auxiliar'");
    }

    public function down(): void
    {
        // Restaurar al ENUM original (los valores fuera del enum se perderán)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Auxiliar','Administrador','Coordinador') NOT NULL DEFAULT 'Auxiliar'");
    }
};
