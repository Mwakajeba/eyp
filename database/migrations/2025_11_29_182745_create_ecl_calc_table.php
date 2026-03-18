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
        if (Schema::hasTable('ecl_calc')) {
            return;
        }

        Schema::create('ecl_calc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('investment_id')->nullable()->constrained('investment_master')->onDelete('cascade');
            $table->foreignId('ecl_input_id')->nullable()->constrained('ecl_inputs')->onDelete('set null');
            $table->foreignId('ecl_scenario_id')->nullable()->constrained('ecl_scenarios')->onDelete('set null');
            
            // Calculation period
            $table->date('calculation_date')->index();
            $table->string('calculation_run_id', 100)->index(); // Batch run identifier
            $table->enum('calculation_type', ['12_MONTH', 'LIFETIME', 'SIMPLIFIED'])->default('12_MONTH');
            
            // Stage information
            $table->integer('stage')->default(1); // IFRS 9 stage (1, 2, or 3)
            $table->date('stage_assigned_date')->nullable();
            $table->text('stage_reason')->nullable(); // Reason for stage assignment
            
            // ECL Parameters
            $table->decimal('pd', 10, 6)->default(0); // Probability of default (%)
            $table->decimal('lgd', 10, 6)->default(45); // Loss given default (%)
            $table->decimal('ead', 18, 2)->default(0); // Exposure at default
            $table->decimal('ccf', 10, 6)->default(100); // Credit conversion factor (%)
            
            // ECL Calculation Results
            $table->decimal('ecl_12_month', 18, 2)->default(0); // 12-month ECL
            $table->decimal('ecl_lifetime', 18, 2)->default(0); // Lifetime ECL
            $table->decimal('ecl_amount', 18, 2)->default(0); // Final ECL amount (stage-based)
            
            // Scenario-based calculations
            $table->json('scenario_ecl')->nullable(); // ECL by scenario
            $table->decimal('weighted_ecl', 18, 2)->default(0); // Weighted average ECL
            
            // Forward-looking adjustments
            $table->json('forward_looking_adjustments')->nullable();
            $table->decimal('pd_adjustment', 10, 6)->default(0); // PD adjustment applied
            $table->boolean('forward_looking_applied')->default(false);
            
            // Model information
            $table->string('model_name', 200)->nullable();
            $table->string('model_version', 50)->nullable();
            
            // Journal posting
            $table->foreignId('posted_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            
            // Status
            $table->enum('status', ['DRAFT', 'CALCULATED', 'REVIEWED', 'APPROVED', 'POSTED'])->default('CALCULATED');
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'calculation_date']);
            $table->index(['investment_id', 'calculation_date']);
            $table->index(['calculation_run_id', 'calculation_date']);
            $table->index('stage');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecl_calc');
    }
};
