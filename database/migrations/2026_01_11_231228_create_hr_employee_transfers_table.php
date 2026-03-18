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
        Schema::create('hr_employee_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->string('transfer_number', 50)->unique();
            $table->string('transfer_type', 50); // 'department', 'branch', 'location', 'position'
            $table->foreignId('from_department_id')->nullable()->constrained('hr_departments')->onDelete('set null');
            $table->foreignId('to_department_id')->nullable()->constrained('hr_departments')->onDelete('restrict');
            $table->foreignId('from_position_id')->nullable()->constrained('hr_positions')->onDelete('set null');
            $table->foreignId('to_position_id')->nullable()->constrained('hr_positions')->onDelete('restrict');
            $table->foreignId('from_branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('to_branch_id')->nullable()->constrained('branches')->onDelete('restrict');
            $table->date('effective_date');
            $table->text('transfer_reason')->nullable();
            $table->decimal('transfer_allowance', 15, 2)->nullable();
            $table->string('status', 50)->default('pending'); // 'pending', 'approved', 'rejected', 'completed'
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->boolean('cost_center_updated')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['effective_date', 'status']);
            $table->index('transfer_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_transfers');
    }
};
