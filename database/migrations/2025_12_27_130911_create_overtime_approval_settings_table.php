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
        Schema::create('overtime_approval_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Basic approval configuration
            $table->boolean('approval_required')->default(false);
            $table->integer('approval_levels')->default(1);
            
            // Level 1 Configuration
            $table->decimal('level1_hours_threshold', 8, 2)->nullable();
            $table->json('level1_approvers')->nullable();
            
            // Level 2 Configuration
            $table->decimal('level2_hours_threshold', 8, 2)->nullable();
            $table->json('level2_approvers')->nullable();
            
            // Level 3 Configuration
            $table->decimal('level3_hours_threshold', 8, 2)->nullable();
            $table->json('level3_approvers')->nullable();
            
            // Level 4 Configuration
            $table->decimal('level4_hours_threshold', 8, 2)->nullable();
            $table->json('level4_approvers')->nullable();
            
            // Level 5 Configuration
            $table->decimal('level5_hours_threshold', 8, 2)->nullable();
            $table->json('level5_approvers')->nullable();
            
            // Additional configuration
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'branch_id']);
            $table->unique(['company_id', 'branch_id'], 'overtime_approval_settings_company_branch_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_approval_settings');
    }
};
