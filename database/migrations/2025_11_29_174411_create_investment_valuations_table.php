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
        Schema::create('investment_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained('investment_master')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Valuation date and period
            $table->date('valuation_date')->index();
            $table->date('period_start')->nullable(); // For period-based valuations
            $table->date('period_end')->nullable();
            
            // Fair Value Hierarchy (IFRS 13)
            $table->integer('valuation_level')->default(1); // 1, 2, or 3
            $table->enum('valuation_method', ['MARKET_PRICE', 'YIELD_CURVE', 'DCF', 'NAV', 'BANK_VALUATION', 'MANUAL'])->default('MARKET_PRICE');
            
            // Fair Value Calculation
            $table->decimal('fair_value_per_unit', 18, 6)->default(0);
            $table->decimal('units', 18, 6)->default(0); // Units at valuation date
            $table->decimal('total_fair_value', 18, 2)->default(0); // fair_value_per_unit × units
            $table->decimal('carrying_amount_before', 18, 2)->default(0); // Carrying amount before revaluation
            $table->decimal('carrying_amount_after', 18, 2)->default(0); // Carrying amount after revaluation
            
            // Gain/Loss Calculation
            $table->decimal('unrealized_gain_loss', 18, 2)->default(0); // Change in fair value
            $table->decimal('realized_gain_loss', 18, 2)->default(0); // For disposals
            $table->decimal('fvoci_reserve_change', 18, 2)->default(0); // For FVOCI investments
            
            // Valuation Inputs (Level 2 & 3)
            $table->decimal('yield_rate', 18, 12)->nullable(); // For yield curve discounting
            $table->decimal('discount_rate', 18, 12)->nullable(); // For DCF
            $table->json('cash_flows')->nullable(); // Expected cash flows for DCF
            $table->json('valuation_inputs')->nullable(); // Additional inputs (JSON)
            $table->text('valuation_assumptions')->nullable(); // Assumptions for Level 3
            
            // Market Data Source
            $table->string('price_source', 200)->nullable(); // BOT, DSE, Bloomberg, Manual, etc.
            $table->string('price_reference', 200)->nullable(); // Reference number or identifier
            $table->date('price_date')->nullable(); // Date of the market price used
            
            // Approval & Status
            $table->enum('status', ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'POSTED'])->default('DRAFT');
            $table->boolean('requires_approval')->default(false); // Level 3 valuations require approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Journal Posting
            $table->foreignId('posted_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['investment_id', 'valuation_date']);
            $table->index(['company_id', 'valuation_date']);
            $table->index(['status', 'valuation_date']);
            $table->index('valuation_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_valuations');
    }
};
