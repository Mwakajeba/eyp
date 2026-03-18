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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_submitted_by')->nullable()->after('payment_rejection_remarks');
            $table->timestamp('payment_submitted_at')->nullable()->after('payment_submitted_by');
            $table->unsignedBigInteger('payment_bank_account_id')->nullable()->after('payment_submitted_at');
            $table->unsignedBigInteger('payment_chart_account_id')->nullable()->after('payment_bank_account_id');
            $table->date('payment_date')->nullable()->after('payment_chart_account_id');
            
            $table->foreign('payment_submitted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('payment_bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('payment_chart_account_id')->references('id')->on('chart_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['payment_submitted_by']);
            $table->dropForeign(['payment_bank_account_id']);
            $table->dropForeign(['payment_chart_account_id']);
            
            $table->dropColumn([
                'payment_submitted_by',
                'payment_submitted_at',
                'payment_bank_account_id',
                'payment_chart_account_id',
                'payment_date'
            ]);
        });
    }
};
