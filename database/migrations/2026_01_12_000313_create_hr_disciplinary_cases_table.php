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
        Schema::create('hr_disciplinary_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->string('case_number', 50)->unique();
            $table->string('case_category', 50); // 'misconduct', 'absenteeism', 'performance'
            $table->date('incident_date');
            $table->foreignId('reported_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('description');
            $table->string('status', 50)->default('open'); // 'open', 'investigating', 'resolved', 'closed'
            $table->string('outcome', 50)->nullable(); // 'verbal_warning', 'written_warning', 'suspension', 'termination'
            $table->date('outcome_date')->nullable();
            $table->json('payroll_impact')->nullable(); // e.g., {'unpaid_suspension_days': 3}
            $table->text('resolution_notes')->nullable();
            $table->foreignId('investigated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['case_category', 'status']);
            $table->index('incident_date');
            $table->index('case_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_disciplinary_cases');
    }
};
