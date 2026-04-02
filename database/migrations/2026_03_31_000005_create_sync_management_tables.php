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
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->enum('source_type', ['csv', 'excel', 'manual'])->default('excel');
            $table->enum('status', ['draft', 'validated', 'applied', 'failed', 'cancelled'])->default('draft');
            $table->json('selected_columns')->nullable();
            $table->json('column_mapping')->nullable();
            $table->json('preview_summary')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('sync_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->enum('origin', ['manual', 'import', 'master_update'])->default('manual');
            $table->enum('status', ['pending', 'approved', 'running', 'partial', 'success', 'failed'])->default('pending');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('payload')->nullable();
            $table->text('error_summary')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'approval_status']);
        });

        Schema::create('sync_batch_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_batch_id')->constrained('sync_batches')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'retry'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['sync_batch_id', 'branch_id']);
            $table->index(['status', 'attempts']);
        });

        Schema::create('branch_sync_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('rule_key');
            $table->json('rule_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'rule_key']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('sync_batch_id')->nullable()->constrained('sync_batches')->nullOnDelete();
            $table->json('changes')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('branch_sync_rules');
        Schema::dropIfExists('sync_batch_targets');
        Schema::dropIfExists('sync_batches');
        Schema::dropIfExists('import_batches');
    }
};
