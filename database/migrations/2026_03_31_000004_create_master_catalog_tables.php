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
        Schema::create('master_articles', function (Blueprint $table) {
            $table->id();
            $table->string('master_code')->unique();
            $table->string('canonical_description');
            $table->string('brand')->nullable();
            $table->string('unit', 50)->nullable();
            $table->enum('status', ['draft', 'active', 'inactive', 'obsolete'])->default('draft');
            $table->foreignId('source_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('source_article_code')->nullable();
            $table->decimal('current_price', 12, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('standardized_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'master_code']);
        });

        Schema::create('branch_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('branch_article_code');
            $table->string('branch_description');
            $table->enum('status', ['active', 'inactive', 'obsolete', 'unknown'])->default('unknown');
            $table->boolean('is_enabled')->default(true);
            $table->decimal('current_price', 12, 2)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'branch_article_code']);
            $table->index(['branch_id', 'status']);
        });

        Schema::create('homologation_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_article_id')->constrained('branch_articles')->cascadeOnDelete();
            $table->foreignId('master_article_id')->nullable()->constrained('master_articles')->nullOnDelete();
            $table->enum('match_type', ['pending', 'automatic', 'manual', 'rejected'])->default('pending');
            $table->decimal('confidence', 5, 2)->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['match_type', 'reviewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homologation_matches');
        Schema::dropIfExists('branch_articles');
        Schema::dropIfExists('master_articles');
    }
};
