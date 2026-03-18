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
        Schema::create('hr_grievances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->string('grievance_number', 50)->unique();
            $table->string('complaint_type', 50); // 'harassment', 'discrimination', 'workplace', 'salary', 'other'
            $table->text('description');
            $table->string('priority', 50)->default('medium'); // 'low', 'medium', 'high', 'urgent'
            $table->string('status', 50)->default('open'); // 'open', 'investigating', 'resolved', 'closed'
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // HR case officer
            $table->text('resolution')->nullable();
            $table->text('investigation_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['complaint_type', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('grievance_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_grievances');
    }
};
