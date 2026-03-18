<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_drivers', function (Blueprint $table) {
            $table->foreignId('fuel_card_bank_account_id')->nullable()->after('assigned_vehicle_id')
                ->constrained('bank_accounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_drivers', function (Blueprint $table) {
            $table->dropForeign(['fuel_card_bank_account_id']);
        });
    }
};
