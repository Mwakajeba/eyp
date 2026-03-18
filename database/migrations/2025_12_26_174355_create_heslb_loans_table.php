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
        Schema::create('hr_heslb_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->string('loan_number')->nullable()->comment('HESLB loan reference number');
            $table->decimal('original_loan_amount', 15, 2)->default(0)->comment('Original loan amount');
            $table->decimal('outstanding_balance', 15, 2)->default(0)->comment('Current outstanding balance');
            $table->date('loan_start_date')->nullable()->comment('When the loan was issued');
            $table->date('loan_end_date')->nullable()->comment('Expected completion date');
            $table->boolean('is_active')->default(true)->comment('Whether this loan is currently active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['employee_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_heslb_loans');
    }
};
