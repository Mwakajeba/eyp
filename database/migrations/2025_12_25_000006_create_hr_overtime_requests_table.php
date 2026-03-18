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
        if (Schema::hasTable('hr_overtime_requests')) {
            return;
        }

        Schema::create('hr_overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('attendance_id')->nullable()->constrained('hr_attendance')->onDelete('set null');
            $table->date('overtime_date')->notNull();
            $table->decimal('overtime_hours', 4, 2)->notNull();
            $table->decimal('overtime_rate', 5, 2)->default(1.50);
            $table->text('reason')->nullable();
            $table->string('status', 50)->default('pending'); // 'pending', 'approved', 'rejected'
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'overtime_date']);
            $table->index('status');
            $table->index('overtime_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_overtime_requests');
    }
};

