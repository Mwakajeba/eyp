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
        Schema::table('investment_master', function (Blueprint $table) {
            if (!Schema::hasColumn('investment_master', 'carrying_amount')) {
                $table->decimal('carrying_amount', 18, 2)->default(0)->after('nominal_amount');
            }
            if (!Schema::hasColumn('investment_master', 'fvoci_reserve')) {
                $table->decimal('fvoci_reserve', 18, 2)->default(0)->after('carrying_amount'); // For FVOCI investments
            }
            if (!Schema::hasColumn('investment_master', 'gl_fvoci_reserve_account')) {
                $table->foreignId('gl_fvoci_reserve_account')->nullable()->constrained('chart_accounts')->onDelete('set null')->after('gl_gain_loss_account');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_master', function (Blueprint $table) {
            if (Schema::hasColumn('investment_master', 'carrying_amount')) {
                $table->dropColumn('carrying_amount');
            }
            if (Schema::hasColumn('investment_master', 'fvoci_reserve')) {
                $table->dropColumn('fvoci_reserve');
            }
            if (Schema::hasColumn('investment_master', 'gl_fvoci_reserve_account')) {
                $table->dropForeign(['gl_fvoci_reserve_account']);
                $table->dropColumn('gl_fvoci_reserve_account');
            }
        });
    }
};
