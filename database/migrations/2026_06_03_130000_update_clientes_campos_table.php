<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes_campos', function (Blueprint $table) {
            $table->boolean('show_in_create')->default(false)->after('is_required');
            $table->boolean('show_in_edit')->default(false)->after('show_in_create');
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('clientes_campos', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->dropColumn(['show_in_create', 'show_in_edit']);
        });
    }
};
