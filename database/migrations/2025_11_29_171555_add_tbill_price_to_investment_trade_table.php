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
            if (!Schema::hasColumn('investment_trade', 'tbill_price')) {
                $table->decimal('tbill_price', 18, 6)->nullable()->after('bond_price'); // BOT auction price for T-Bills
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_trade', function (Blueprint $table) {
            if (Schema::hasColumn('investment_trade', 'tbill_price')) {
                $table->dropColumn('tbill_price');
            }
        });
    }
};
