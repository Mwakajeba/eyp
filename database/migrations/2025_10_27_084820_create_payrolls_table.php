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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->decimal('total_salary', 15, 2)->default(0);
            $table->decimal('total_allowance', 15, 2)->default(0);
            $table->decimal('total_nhif_employee', 15, 2)->default(0);
            $table->decimal('total_nhif_employer', 15, 2)->default(0);
            $table->decimal('total_pension_employee', 15, 2)->default(0);
            $table->decimal('total_pension_employer', 15, 2)->default(0);
            $table->decimal('total_wcf', 15, 2)->default(0);
            $table->decimal('total_sdl', 15, 2)->default(0);
            $table->decimal('total_heslb', 15, 2)->default(0);
            $table->decimal('total_trade_union', 15, 2)->default(0);
            $table->decimal('total_payee', 15, 2)->default(0);
            $table->decimal('total_salary_advance_paid', 15, 2)->default(0);
            $table->decimal('total_external_loan_paid', 15, 2)->default(0);
            $table->enum('status', ['draft', 'processing', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('company_id')->constrained('companies');
            $table->timestamps();

            // Add unique constraint to prevent duplicate payrolls for same month/year
            $table->unique(['year', 'month', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
