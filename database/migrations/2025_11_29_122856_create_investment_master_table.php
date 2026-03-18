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
        Schema::create('investment_master', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Instrument identification
            $table->string('instrument_code', 50)->unique();
            $table->enum('instrument_type', ['T_BILL', 'T_BOND', 'FIXED_DEPOSIT', 'CORP_BOND', 'EQUITY', 'MMF', 'OTHER'])->default('OTHER');
            $table->string('issuer', 200)->nullable();
            $table->string('isin', 50)->nullable();
            
            // Dates
            $table->date('purchase_date')->nullable();
            $table->date('settlement_date')->nullable();
            $table->date('maturity_date')->nullable();
            
            // Financial details
            $table->decimal('nominal_amount', 18, 2)->default(0);
            $table->decimal('purchase_price', 18, 6)->default(0); // per unit
            $table->decimal('units', 18, 6)->default(0); // quantity
            $table->string('currency', 10)->default('TZS');
            
            // Accounting classification (IFRS 9)
            $table->enum('accounting_class', ['AMORTISED_COST', 'FVOCI', 'FVPL'])->default('AMORTISED_COST');
            $table->decimal('eir_rate', 18, 12)->nullable(); // Effective Interest Rate
            $table->string('day_count', 20)->default('ACT/365'); // ACT/365, ACT/360, 30/360
            $table->decimal('coupon_rate', 10, 6)->nullable();
            $table->integer('coupon_freq')->nullable(); // payments per year
            $table->json('coupon_schedule')->nullable(); // list of {date, amount}
            
            // Status and classification
            $table->enum('status', ['DRAFT', 'ACTIVE', 'REDEEMED', 'DISPOSED', 'MATURED'])->default('DRAFT');
            $table->integer('valuation_level')->default(1); // 1, 2, or 3
            $table->integer('impairment_stage')->default(1); // 1, 2, or 3 for ECL
            $table->string('tax_class', 50)->nullable();
            
            // GL Account mappings
            $table->foreignId('gl_asset_account')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->foreignId('gl_accrued_interest_account')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->foreignId('gl_interest_income_account')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->foreignId('gl_gain_loss_account')->nullable()->constrained('chart_accounts')->onDelete('set null');
            
            // Attachments and metadata
            $table->json('attachments')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('instrument_code');
            $table->index('instrument_type');
            $table->index('maturity_date');
            $table->index('accounting_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_master');
    }
};
