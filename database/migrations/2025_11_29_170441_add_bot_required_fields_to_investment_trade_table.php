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
            // BOT Required Fields for T-Bonds
            $table->string('auction_no', 100)->nullable()->after('counterparty');
            $table->date('auction_date')->nullable()->after('auction_no');
            $table->string('bond_type', 50)->nullable()->after('auction_date'); // 2-years, 5-years, 7-years, 10-years, 15-years, 20-years, 25-years, etc.
            $table->decimal('bond_price', 18, 6)->nullable()->after('bond_type'); // BOT auction price
            // BOT Required Fields for T-Bills
            $table->decimal('tbill_price', 18, 6)->nullable()->after('bond_price'); // BOT auction price for T-Bills
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_trade', function (Blueprint $table) {
            $table->dropColumn(['auction_no', 'auction_date', 'bond_type', 'bond_price', 'tbill_price']);
        });
    }
};
