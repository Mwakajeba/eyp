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
        Schema::create('hr_salary_advance_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_advance_id')->constrained('hr_salary_advances')->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('set null');
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->onDelete('set null');
            
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('type'); // 'manual' or 'payroll'
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // Add repayment_type to salary_advances
        Schema::table('hr_salary_advances', function (Blueprint $table) {
            $table->string('repayment_type')->default('payroll')->after('monthly_deduction'); // payroll, manual, both
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_advances', function (Blueprint $table) {
            $table->dropColumn('repayment_type');
        });
        Schema::dropIfExists('hr_salary_advance_repayments');
    }
};
