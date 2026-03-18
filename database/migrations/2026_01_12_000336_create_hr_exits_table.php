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
        Schema::create('hr_exits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->string('exit_number', 50)->unique();
            $table->string('exit_type', 50); // 'resignation', 'termination', 'retirement', 'contract_expiry', 'redundancy'
            $table->date('resignation_date')->nullable(); // If resignation
            $table->date('effective_date');
            $table->integer('notice_period_days')->nullable();
            $table->text('exit_reason')->nullable();
            $table->text('exit_interview_notes')->nullable();
            $table->string('clearance_status', 50)->default('pending'); // 'pending', 'in_progress', 'completed'
            $table->string('final_pay_status', 50)->default('pending'); // 'pending', 'calculated', 'approved', 'paid'
            $table->decimal('final_pay_amount', 15, 2)->nullable();
            $table->text('final_pay_notes')->nullable();
            $table->boolean('exit_interview_conducted')->default(false);
            $table->foreignId('initiated_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'exit_type']);
            $table->index(['clearance_status', 'final_pay_status']);
            $table->index('effective_date');
            $table->index('exit_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_exits');
    }
};
