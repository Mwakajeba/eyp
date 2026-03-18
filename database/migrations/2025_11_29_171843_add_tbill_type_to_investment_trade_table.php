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
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('investment_trade', 'tbill_type')) {
                $table->string('tbill_type', 50)->nullable()->after('tbill_price'); // 35-days, 91-days, 182-days, 364-days, etc.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_trade', function (Blueprint $table) {
            if (Schema::hasColumn('investment_trade', 'tbill_type')) {
                $table->dropColumn('tbill_type');
            }
        });
    }
};
