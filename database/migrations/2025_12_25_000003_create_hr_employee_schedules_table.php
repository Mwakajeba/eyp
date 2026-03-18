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
        if (Schema::hasTable('hr_employee_schedules')) {
            return;
        }

        Schema::create('hr_employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('schedule_id')->nullable()->constrained('hr_work_schedules')->onDelete('set null');
            $table->foreignId('shift_id')->nullable()->constrained('hr_shifts')->onDelete('set null');
            $table->date('effective_date')->notNull();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date']);
            $table->index(['schedule_id', 'effective_date']);
            $table->index(['shift_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_schedules');
    }
};

