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
        Schema::create('hr_timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->date('timesheet_date')->notNull();
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->onDelete('set null');
            $table->string('activity_type', 50)->default('work'); // 'work', 'training', 'meeting', 'conference', 'project', 'other'
            $table->string('project_reference')->nullable(); // Optional project reference/code
            $table->decimal('normal_hours', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->text('description')->nullable();
            $table->text('priorities')->nullable(); // Priorities for the day
            $table->text('achievements')->nullable(); // Achievements/accomplishments
            $table->string('status', 50)->default('draft'); // 'draft', 'submitted', 'approved', 'rejected'
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'timesheet_date']);
            $table->index(['timesheet_date', 'status']);
            $table->index('department_id');
            $table->index('activity_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_timesheets');
    }
};
