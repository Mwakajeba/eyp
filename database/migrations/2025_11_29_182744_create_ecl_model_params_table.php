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
        Schema::create('ecl_model_params', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('model_name', 200)->index();
            $table->string('model_version', 50)->default('1.0');
            $table->enum('instrument_type', ['T_BILL', 'T_BOND', 'FIXED_DEPOSIT', 'CORP_BOND', 'EQUITY', 'MMF', 'COMMERCIAL_PAPER', 'ALL'])->default('ALL');
            $table->enum('stage', [1, 2, 3])->nullable(); // Stage-specific params
            
            // PD Parameters
            $table->decimal('base_pd', 10, 6)->default(0); // Base probability of default
            $table->json('pd_adjustment_factors')->nullable(); // Macro-economic adjustments
            $table->json('pd_rating_matrix')->nullable(); // Rating-based PD matrix
            
            // LGD Parameters
            $table->decimal('base_lgd', 10, 6)->default(45); // Base loss given default (%)
            $table->json('lgd_adjustment_factors')->nullable();
            $table->decimal('collateral_haircut', 10, 6)->default(0); // Collateral haircut %
            
            // EAD Parameters
            $table->decimal('ccf', 10, 6)->default(100); // Credit Conversion Factor (%)
            $table->json('ead_adjustment_rules')->nullable();
            
            // Model Configuration
            $table->json('staging_rules')->nullable(); // SICR detection rules
            $table->json('scenario_weights')->nullable(); // Scenario weighting
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'model_name', 'is_active']);
            $table->index(['instrument_type', 'stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecl_model_params');
    }
};
