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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('db_host');
            $table->unsignedInteger('db_port')->default(3306);
            $table->string('db_user', 100);
            $table->string('db_password');
            $table->string('db_database', 100);
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->timestamp('last_connection_check')->nullable();
            $table->string('connection_status', 50)->default('pending');
            $table->timestamps();

            $table->index('code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
