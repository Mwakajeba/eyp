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
        Schema::table('asset_categories', function (Blueprint $table) {
            // Revaluation model settings
            $table->enum('default_valuation_model', ['cost', 'revaluation'])->default('cost')->after('revaluation_reserve_account_id');
            $table->enum('revaluation_frequency', ['annual', 'biennial', 'ad_hoc'])->nullable()->after('default_valuation_model');
            $table->integer('revaluation_interval_years')->nullable()->after('revaluation_frequency');
            
            // Impairment default accounts
            $table->unsignedBigInteger('impairment_loss_account_id')->nullable()->after('revaluation_interval_years');
            $table->unsignedBigInteger('impairment_reversal_account_id')->nullable()->after('impairment_loss_account_id');
            $table->unsignedBigInteger('accumulated_impairment_account_id')->nullable()->after('impairment_reversal_account_id');
            
            // Revaluation settings
            $table->boolean('require_valuation_report')->default(false)->after('accumulated_impairment_account_id');
            $table->boolean('require_approval')->default(true)->after('require_valuation_report');
            $table->integer('min_approval_levels')->default(2)->after('require_approval'); // 1 = Finance Manager, 2 = CFO/Board
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_categories', function (Blueprint $table) {
            $table->dropColumn([
                'default_valuation_model',
                'revaluation_frequency',
                'revaluation_interval_years',
                'impairment_loss_account_id',
                'impairment_reversal_account_id',
                'accumulated_impairment_account_id',
                'require_valuation_report',
                'require_approval',
                'min_approval_levels'
            ]);
        });
    }
};
