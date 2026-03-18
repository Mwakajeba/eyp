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
        Schema::create('ecl_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('scenario_name', 200)->index();
            $table->enum('scenario_type', ['BASE', 'OPTIMISTIC', 'PESSIMISTIC', 'CUSTOM'])->default('BASE');
            $table->decimal('weight', 5, 4)->default(0.3333); // Weight for weighted average (0-1)
            
            // Macro-economic factors
            $table->decimal('gdp_growth', 10, 6)->nullable(); // GDP growth rate
            $table->decimal('inflation_rate', 10, 6)->nullable(); // Inflation rate
            $table->decimal('interest_rate', 10, 6)->nullable(); // Interest rate
            $table->decimal('unemployment_rate', 10, 6)->nullable(); // Unemployment rate
            $table->json('macro_factors')->nullable(); // Additional macro factors
            
            // Credit indicators
            $table->decimal('pd_multiplier', 10, 6)->default(1.0); // PD adjustment multiplier
            $table->json('credit_indicators')->nullable(); // Additional credit indicators
            
            // Period
            $table->date('as_of_date')->index();
            $table->date('forecast_period_start')->nullable();
            $table->date('forecast_period_end')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'as_of_date', 'is_active']);
            $table->index('scenario_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecl_scenarios');
    }
};
