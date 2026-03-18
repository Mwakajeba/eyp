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
        Schema::table('assets', function (Blueprint $table) {
            // Revaluation model configuration
            $table->enum('valuation_model', ['cost', 'revaluation'])->default('cost')->after('status');
            $table->enum('revaluation_frequency', ['annual', 'biennial', 'ad_hoc'])->nullable()->after('valuation_model');
            $table->date('last_revaluation_date')->nullable()->after('revaluation_frequency');
            $table->date('next_revaluation_date')->nullable()->after('last_revaluation_date');
            
            // Revaluation reserve
            $table->unsignedBigInteger('revaluation_reserve_account_id')->nullable()->after('next_revaluation_date');
            $table->decimal('revaluation_reserve_balance', 18, 2)->default(0)->after('revaluation_reserve_account_id');
            
            // Accumulated impairment
            $table->decimal('accumulated_impairment', 18, 2)->default(0)->after('revaluation_reserve_balance');
            $table->unsignedBigInteger('accumulated_impairment_account_id')->nullable()->after('accumulated_impairment');
            
            // Current carrying amount (updated after revaluation/impairment)
            $table->decimal('revalued_carrying_amount', 18, 2)->nullable()->after('accumulated_impairment_account_id');
            
            // Impairment status
            $table->boolean('is_impaired')->default(false)->after('revalued_carrying_amount');
            $table->date('last_impairment_date')->nullable()->after('is_impaired');
            
            // CGU (Cash Generating Unit) for grouped impairment
            $table->unsignedBigInteger('cgu_id')->nullable()->after('last_impairment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'valuation_model',
                'revaluation_frequency',
                'last_revaluation_date',
                'next_revaluation_date',
                'revaluation_reserve_account_id',
                'revaluation_reserve_balance',
                'accumulated_impairment',
                'accumulated_impairment_account_id',
                'revalued_carrying_amount',
                'is_impaired',
                'last_impairment_date',
                'cgu_id'
            ]);
        });
    }
};
