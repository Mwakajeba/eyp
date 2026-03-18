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
        Schema::table('hr_payroll_chart_accounts', function (Blueprint $table) {
            // Add external loan payable account field after salary advance receivable account
            if (!Schema::hasColumn('hr_payroll_chart_accounts', 'external_loan_payable_account_id')) {
                $table->foreignId('external_loan_payable_account_id')->nullable()->after('salary_advance_receivable_account_id');
                $table->foreign('external_loan_payable_account_id', 'fk_hr_pca_external_loan_payable')
                      ->references('id')
                      ->on('chart_accounts')
                      ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_payroll_chart_accounts', function (Blueprint $table) {
            // Drop foreign key constraint first
            if (Schema::hasColumn('hr_payroll_chart_accounts', 'external_loan_payable_account_id')) {
                $table->dropForeign('fk_hr_pca_external_loan_payable');
                // Drop the column
                $table->dropColumn('external_loan_payable_account_id');
            }
        });
    }
};
