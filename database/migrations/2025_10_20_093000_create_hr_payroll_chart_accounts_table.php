<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('hr_payroll_chart_accounts');
        Schema::create('hr_payroll_chart_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('salary_advance_receivable_account_id')->nullable();
            $table->foreign('salary_advance_receivable_account_id', 'fk_hr_pca_salary_adv_recv')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('allowance_expense_account_id')->nullable();
            $table->foreign('allowance_expense_account_id', 'fk_hr_pca_allow_exp')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('heslb_expense_account_id')->nullable();
            $table->foreign('heslb_expense_account_id', 'fk_hr_pca_heslb_exp')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('heslb_payable_account_id')->nullable();
            $table->foreign('heslb_payable_account_id', 'fk_hr_pca_heslb_pay')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('pension_expense_account_id')->nullable();
            $table->foreign('pension_expense_account_id', 'fk_hr_pca_pens_exp')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('pension_payable_account_id')->nullable();
            $table->foreign('pension_payable_account_id', 'fk_hr_pca_pens_pay')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('payee_expense_account_id')->nullable();
            $table->foreign('payee_expense_account_id', 'fk_hr_pca_payee_exp')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('payee_payable_account_id')->nullable();
            $table->foreign('payee_payable_account_id', 'fk_hr_pca_payee_pay')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('insurance_expense_account_id')->nullable();
            $table->foreign('insurance_expense_account_id', 'fk_hr_pca_ins_exp')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('insurance_payable_account_id')->nullable();
            $table->foreign('insurance_payable_account_id', 'fk_hr_pca_ins_pay')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('wcf_payable_account_id')->nullable();
            $table->foreign('wcf_payable_account_id', 'fk_hr_pca_wcf_pay')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('wcf_expense_account_id')->nullable();
            $table->foreign('wcf_expense_account_id', 'fk_hr_pca_wcf_exp')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('trade_union_expense_account_id')->nullable();
            $table->foreign('trade_union_expense_account_id', 'fk_hr_pca_tu_exp')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('trade_union_payable_account_id')->nullable();
            $table->foreign('trade_union_payable_account_id', 'fk_hr_pca_tu_pay')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('sdl_expense_account_id')->nullable();
            $table->foreign('sdl_expense_account_id', 'fk_hr_pca_sdl_exp')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('sdl_payable_account_id')->nullable();
            $table->foreign('sdl_payable_account_id', 'fk_hr_pca_sdl_pay')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->foreignId('other_payable_account_id')->nullable();
            $table->foreign('other_payable_account_id', 'fk_hr_pca_other_pay')->references('id')->on('chart_accounts')->nullOnDelete();
            $table->timestamps();
            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payroll_chart_accounts');
    }
};


