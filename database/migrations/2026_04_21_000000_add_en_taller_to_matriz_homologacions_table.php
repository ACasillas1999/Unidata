<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('matriz_homologacions', function (Blueprint $table) {
            if (! Schema::hasColumn('matriz_homologacions', 'en_taller')) {
                $table->tinyInteger('en_taller')->nullable()->default(null)->after('en_washington');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matriz_homologacions', function (Blueprint $table) {
            if (Schema::hasColumn('matriz_homologacions', 'en_taller')) {
                $table->dropColumn('en_taller');
            }
        });
    }
};
