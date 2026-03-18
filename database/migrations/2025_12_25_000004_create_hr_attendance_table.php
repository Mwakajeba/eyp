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
        if (Schema::hasTable('hr_attendance')) {
            return;
        }

        Schema::create('hr_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->date('attendance_date')->notNull();
            $table->foreignId('schedule_id')->nullable()->constrained('hr_work_schedules')->onDelete('set null');
            $table->foreignId('shift_id')->nullable()->constrained('hr_shifts')->onDelete('set null');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->decimal('expected_hours', 4, 2)->nullable();
            $table->decimal('actual_hours', 4, 2)->nullable();
            $table->decimal('normal_hours', 4, 2)->default(0);
            $table->decimal('overtime_hours', 4, 2)->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_exit_minutes')->default(0);
            $table->string('status', 50)->default('present'); // 'present', 'absent', 'late', 'early_exit', 'on_leave'
            $table->string('exception_type', 50)->nullable(); // 'late', 'early_exit', 'missing_punch', 'absent'
            $table->text('exception_reason')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
            $table->index('is_approved');
            $table->index('exception_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_attendance');
    }
};

