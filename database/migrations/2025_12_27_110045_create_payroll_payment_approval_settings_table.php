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
        Schema::create('payroll_payment_approval_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Basic approval configuration
            $table->boolean('payment_approval_required')->default(false);
            $table->integer('payment_approval_levels')->default(1);
            
            // Level 1 Configuration
            $table->decimal('payment_level1_amount_threshold', 15, 2)->nullable();
            $table->json('payment_level1_approvers')->nullable();
            
            // Level 2 Configuration
            $table->decimal('payment_level2_amount_threshold', 15, 2)->nullable();
            $table->json('payment_level2_approvers')->nullable();
            
            // Level 3 Configuration
            $table->decimal('payment_level3_amount_threshold', 15, 2)->nullable();
            $table->json('payment_level3_approvers')->nullable();
            
            // Level 4 Configuration
            $table->decimal('payment_level4_amount_threshold', 15, 2)->nullable();
            $table->json('payment_level4_approvers')->nullable();
            
            // Level 5 Configuration
            $table->decimal('payment_level5_amount_threshold', 15, 2)->nullable();
            $table->json('payment_level5_approvers')->nullable();
            
            // Additional configuration
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'branch_id']);
            $table->unique(['company_id', 'branch_id'], 'payroll_payment_approval_settings_company_branch_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_payment_approval_settings');
    }
};
