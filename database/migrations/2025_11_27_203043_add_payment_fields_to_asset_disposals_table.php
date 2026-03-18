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
        Schema::table('asset_disposals', function (Blueprint $table) {
            $table->decimal('amount_paid', 18, 2)->default(0)->after('disposal_proceeds');
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('amount_paid');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_disposals', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['amount_paid', 'bank_account_id']);
        });
    }
};
