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
        Schema::create('hfs_discontinued_flags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hfs_id');
            
            // Discontinued operation flag
            $table->boolean('is_discontinued')->default(false);
            $table->date('discontinued_date')->nullable();
            
            // Criteria checks (IFRS 5.32)
            $table->json('criteria_checked')->nullable(); // Store criteria evaluation
            // Example: {
            //   "is_component": true,
            //   "represents_separate_major_line": true,
            //   "is_part_of_single_plan": true,
            //   "is_disposed_or_classified_hfs": true
            // }
            
            // Component identification
            $table->string('component_name')->nullable();
            $table->text('component_description')->nullable();
            
            // Effects on P&L (for reporting)
            $table->json('effects_on_pnl')->nullable();
            // Example: {
            //   "revenue": 1000000,
            //   "expenses": 800000,
            //   "pre_tax_profit": 200000,
            //   "tax": 60000,
            //   "post_tax_profit": 140000,
            //   "gain_loss_on_disposal": 50000,
            //   "total_impact": 190000
            // }
            
            // Manual override
            $table->boolean('is_manual_override')->default(false);
            $table->text('override_reason')->nullable();
            $table->unsignedBigInteger('override_approved_by')->nullable();
            $table->dateTime('override_approved_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['hfs_id']);
            $table->index(['is_discontinued']);
            $table->index(['discontinued_date']);
            
            // Foreign keys
            $table->foreign('hfs_id')->references('id')->on('hfs_requests')->onDelete('cascade');
            $table->foreign('override_approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hfs_discontinued_flags');
    }
};
