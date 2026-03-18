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
            if (!Schema::hasColumn('asset_categories', 'revaluation_loss_account_id')) {
                $table->unsignedBigInteger('revaluation_loss_account_id')->nullable()->after('revaluation_reserve_account_id')
                    ->comment('Default chart account for revaluation losses (P&L expense account)');
                $table->foreign('revaluation_loss_account_id')->references('id')->on('chart_accounts')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_categories', function (Blueprint $table) {
            if (Schema::hasColumn('asset_categories', 'revaluation_loss_account_id')) {
                $table->dropForeign(['revaluation_loss_account_id']);
                $table->dropColumn('revaluation_loss_account_id');
            }
        });
    }
};
