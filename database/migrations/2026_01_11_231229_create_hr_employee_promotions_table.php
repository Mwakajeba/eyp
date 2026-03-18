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
        Schema::create('hr_employee_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->string('promotion_number', 50)->unique();
            $table->foreignId('from_job_grade_id')->nullable()->constrained('hr_job_grades')->onDelete('set null');
            $table->foreignId('to_job_grade_id')->constrained('hr_job_grades')->onDelete('restrict');
            $table->foreignId('from_position_id')->nullable()->constrained('hr_positions')->onDelete('set null');
            $table->foreignId('to_position_id')->nullable()->constrained('hr_positions')->onDelete('restrict');
            $table->decimal('from_salary', 15, 2)->nullable();
            $table->decimal('to_salary', 15, 2);
            $table->decimal('salary_increment', 15, 2)->nullable();
            $table->decimal('increment_percentage', 5, 2)->nullable();
            $table->date('effective_date');
            $table->text('promotion_reason')->nullable();
            $table->string('status', 50)->default('pending'); // 'pending', 'approved', 'rejected', 'completed'
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->boolean('salary_updated')->default(false);
            $table->boolean('retroactive_applied')->default(false);
            $table->date('retroactive_from_date')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['effective_date', 'status']);
            $table->index('promotion_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_promotions');
    }
};
