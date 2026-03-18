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
        Schema::table('investment_trade', function (Blueprint $table) {
            // Common fields for bonds (T-Bonds, Corporate Bonds)
            $table->decimal('coupon_rate', 10, 6)->nullable()->after('tax_withheld');
            $table->string('coupon_frequency', 50)->nullable()->after('coupon_rate'); // Semi-annual, Annual, Quarterly
            $table->decimal('yield_to_maturity', 10, 6)->nullable()->after('coupon_frequency');
            $table->decimal('accrued_coupon_at_purchase', 18, 2)->nullable()->after('yield_to_maturity');
            $table->decimal('premium_discount', 18, 2)->nullable()->after('accrued_coupon_at_purchase');
            $table->string('fair_value_source', 100)->nullable()->after('premium_discount'); // BOT price, yield curve, internal model
            $table->decimal('fair_value', 18, 2)->nullable()->after('fair_value_source');
            $table->string('benchmark', 100)->nullable()->after('fair_value'); // Yield curve used
            $table->string('credit_risk_grade', 50)->nullable()->after('benchmark'); // Internal or external rating
            $table->string('counterparty', 200)->nullable()->after('credit_risk_grade'); // Broker / BOT
            
            // T-Bills specific
            $table->decimal('discount_rate', 10, 6)->nullable()->after('counterparty');
            $table->decimal('yield_rate', 10, 6)->nullable()->after('discount_rate');
            $table->integer('maturity_days')->nullable()->after('yield_rate'); // 91/182/364
            
            // Fixed Deposits specific
            $table->string('fd_reference_no', 100)->nullable()->after('maturity_days');
            $table->string('bank_name', 200)->nullable()->after('fd_reference_no');
            $table->string('branch', 100)->nullable()->after('bank_name');
            $table->string('interest_computation_method', 50)->nullable()->after('branch'); // Simple/Compound
            $table->string('payout_frequency', 50)->nullable()->after('interest_computation_method'); // Monthly/Quarterly/End maturity
            $table->decimal('expected_interest', 18, 2)->nullable()->after('payout_frequency');
            $table->boolean('collateral_flag')->default(false)->after('expected_interest');
            $table->boolean('rollover_option')->default(false)->after('collateral_flag');
            $table->decimal('premature_withdrawal_penalty', 18, 2)->nullable()->after('rollover_option');
            
            // Corporate Bonds specific
            $table->string('issuer_name', 200)->nullable()->after('premature_withdrawal_penalty');
            $table->string('sector', 100)->nullable()->after('issuer_name'); // For concentration risk
            $table->string('credit_rating', 50)->nullable()->after('sector'); // External (Fitch/Moody's) or internal
            $table->string('credit_spread', 50)->nullable()->after('credit_rating'); // Used in valuation
            $table->string('fair_value_method', 50)->nullable()->after('credit_spread'); // Market price, DCF
            $table->text('impairment_override_reason')->nullable()->after('fair_value_method'); // For audit
            $table->string('counterparty_broker', 200)->nullable()->after('impairment_override_reason');
            
            // Equity specific
            $table->string('ticker_symbol', 50)->nullable()->after('counterparty_broker'); // DSE or foreign exchange
            $table->string('company_name', 200)->nullable()->after('ticker_symbol');
            $table->decimal('number_of_shares', 18, 6)->nullable()->after('company_name');
            $table->decimal('purchase_price_per_share', 18, 6)->nullable()->after('number_of_shares');
            $table->decimal('dividend_rate', 10, 6)->nullable()->after('purchase_price_per_share');
            $table->decimal('dividend_tax_rate', 10, 6)->nullable()->after('dividend_rate');
            $table->string('country', 100)->nullable()->after('dividend_tax_rate');
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('country'); // If foreign
            $table->boolean('impairment_indicator')->default(false)->after('exchange_rate'); // If unquoted share
            $table->boolean('ecl_not_applicable_flag')->default(true)->after('impairment_indicator'); // Equity is not ECL-scoped
            
            // Money Market Funds specific
            $table->string('fund_name', 200)->nullable()->after('ecl_not_applicable_flag');
            $table->string('fund_manager', 200)->nullable()->after('fund_name');
            $table->decimal('units_purchased', 18, 6)->nullable()->after('fund_manager');
            $table->decimal('unit_price', 18, 6)->nullable()->after('units_purchased');
            $table->decimal('nav_price', 18, 6)->nullable()->after('unit_price');
            $table->decimal('distribution_rate', 10, 6)->nullable()->after('nav_price');
            $table->string('risk_class', 50)->nullable()->after('distribution_rate'); // Low/Medium
            
            // Commercial Papers specific
            $table->string('issuer', 200)->nullable()->after('risk_class');
            
            // IFRS 9 ECL fields (common to most categories)
            $table->integer('stage')->default(1)->nullable()->after('issuer'); // 1, 2, or 3
            $table->decimal('pd', 10, 6)->nullable()->after('stage'); // Probability of Default
            $table->decimal('lgd', 10, 6)->nullable()->after('pd'); // Loss Given Default
            $table->decimal('ead', 18, 2)->nullable()->after('lgd'); // Exposure At Default
            $table->decimal('ecl_amount', 18, 2)->nullable()->after('ead'); // System calculated
            
            // Disposal fields
            $table->date('disposal_date')->nullable()->after('ecl_amount');
            $table->decimal('realized_gain_loss', 18, 2)->nullable()->after('disposal_date');
            
            // Tax fields
            $table->decimal('tax_withholding_rate', 10, 6)->nullable()->after('realized_gain_loss'); // For coupon/interest WHT
            
            // Additional metadata
            $table->json('contractual_cashflows')->nullable()->after('tax_withholding_rate'); // Required for staging
            $table->json('expected_cashflows')->nullable()->after('contractual_cashflows'); // Needed for ECL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_trade', function (Blueprint $table) {
            // Drop all added columns
            $table->dropColumn([
                'coupon_rate', 'coupon_frequency', 'yield_to_maturity', 'accrued_coupon_at_purchase',
                'premium_discount', 'fair_value_source', 'fair_value', 'benchmark', 'credit_risk_grade',
                'counterparty', 'discount_rate', 'yield_rate', 'maturity_days', 'fd_reference_no',
                'bank_name', 'branch', 'interest_computation_method', 'payout_frequency', 'expected_interest',
                'collateral_flag', 'rollover_option', 'premature_withdrawal_penalty', 'issuer_name',
                'sector', 'credit_rating', 'credit_spread', 'fair_value_method', 'impairment_override_reason',
                'counterparty_broker', 'ticker_symbol', 'company_name', 'number_of_shares',
                'purchase_price_per_share', 'dividend_rate', 'dividend_tax_rate', 'country', 'exchange_rate',
                'impairment_indicator', 'ecl_not_applicable_flag', 'fund_name', 'fund_manager',
                'units_purchased', 'unit_price', 'nav_price', 'distribution_rate', 'risk_class',
                'issuer', 'stage', 'pd', 'lgd', 'ead', 'ecl_amount', 'disposal_date', 'realized_gain_loss',
                'tax_withholding_rate', 'contractual_cashflows', 'expected_cashflows'
            ]);
        });
    }
};
