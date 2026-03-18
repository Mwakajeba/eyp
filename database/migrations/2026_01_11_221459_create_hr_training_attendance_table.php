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
        Schema::create('hr_training_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('hr_training_programs')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->string('attendance_status', 50)->default('registered'); // 'registered', 'attended', 'completed', 'absent'
            $table->date('completion_date')->nullable();
            $table->boolean('certification_received')->default(false);
            $table->decimal('evaluation_score', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'employee_id']);
            $table->index(['employee_id', 'attendance_status']);
            $table->index('program_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_training_attendance');
    }
};
