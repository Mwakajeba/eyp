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
            // Add salary payable account field after salary advance receivable account
            $table->foreignId('salary_payable_account_id')->nullable()->after('salary_advance_receivable_account_id');
            $table->foreign('salary_payable_account_id', 'fk_hr_pca_salary_payable')
                  ->references('id')
                  ->on('chart_accounts')
                  ->nullOnDelete();
                  
            // Also add salary expense account for completeness
            $table->foreignId('salary_expense_account_id')->nullable()->after('salary_payable_account_id');
            $table->foreign('salary_expense_account_id', 'fk_hr_pca_salary_expense')
                  ->references('id')
                  ->on('chart_accounts')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_payroll_chart_accounts', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign('fk_hr_pca_salary_payable');
            $table->dropForeign('fk_hr_pca_salary_expense');
            
            // Drop the columns
            $table->dropColumn(['salary_payable_account_id', 'salary_expense_account_id']);
        });
    }
};
