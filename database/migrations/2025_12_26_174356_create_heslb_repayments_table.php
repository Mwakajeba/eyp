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
        Schema::create('hr_heslb_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('heslb_loan_id')->constrained('hr_heslb_loans')->onDelete('cascade');
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->onDelete('set null')->comment('Link to payroll if deducted via payroll');
            $table->foreignId('payroll_employee_id')->nullable()->constrained('payroll_employees')->onDelete('set null');
            $table->decimal('amount', 15, 2)->default(0)->comment('Repayment amount');
            $table->decimal('balance_before', 15, 2)->default(0)->comment('Balance before this repayment');
            $table->decimal('balance_after', 15, 2)->default(0)->comment('Balance after this repayment');
            $table->date('repayment_date')->comment('Date of repayment');
            $table->string('payment_method', 50)->default('payroll')->comment('payroll, manual, other');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['heslb_loan_id', 'repayment_date']);
            $table->index('repayment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_heslb_repayments');
    }
};
