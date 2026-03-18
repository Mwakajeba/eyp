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
        Schema::create('loan_ifrs_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('cash_schedule_id')->nullable(); // Link to corresponding cash schedule
            
            // Period information
            $table->integer('period_no');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date');
            
            // IFRS 9 Amortised Cost Schedule
            $table->decimal('opening_amortised_cost', 15, 2); // Opening balance at amortised cost
            $table->decimal('ifrs_interest_expense', 15, 2); // Interest expense using EIR
            $table->decimal('cash_paid', 15, 2)->default(0); // Cash paid (from cash schedule)
            $table->decimal('closing_amortised_cost', 15, 2); // Closing balance at amortised cost
            
            // Breakdown of cash paid (from cash schedule)
            $table->decimal('cash_interest_paid', 15, 2)->default(0);
            $table->decimal('cash_principal_paid', 15, 2)->default(0);
            
            // Deferred loan costs amortization (if applicable)
            $table->decimal('deferred_costs_amortized', 15, 2)->default(0);
            
            // Effective Interest Rate used (as percentage, e.g., 10.0 for 10%, 13.6 for 13.6%)
            $table->decimal('effective_interest_rate', 8, 4);
            
            // Status tracking
            $table->boolean('posted_to_gl')->default(false);
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->date('posted_date')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('cash_schedule_id')->references('id')->on('loan_cash_schedules')->onDelete('set null');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('set null');
            
            // Indexes
            $table->index(['loan_id', 'period_no']);
            $table->index('due_date');
            $table->index('posted_to_gl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_ifrs_schedules');
    }
};

