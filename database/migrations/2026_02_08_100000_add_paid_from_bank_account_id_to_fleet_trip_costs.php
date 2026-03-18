<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_trip_costs', function (Blueprint $table) {
            $table->foreignId('paid_from_bank_account_id')->nullable()->after('notes')->constrained('bank_accounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_trip_costs', function (Blueprint $table) {
            $table->dropForeign(['paid_from_bank_account_id']);
        });
    }
};
