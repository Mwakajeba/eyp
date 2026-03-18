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
        Schema::create('hr_vacancy_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('position_id')->constrained('hr_positions')->onDelete('restrict');
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->onDelete('set null');
            $table->string('requisition_number', 50)->unique();
            $table->string('job_title', 200);
            $table->text('job_description')->nullable();
            $table->text('requirements')->nullable();
            $table->integer('number_of_positions')->default(1);
            $table->decimal('budgeted_salary_min', 15, 2)->nullable();
            $table->decimal('budgeted_salary_max', 15, 2)->nullable();
            $table->date('opening_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->string('status', 50)->default('draft'); // 'draft', 'pending_approval', 'approved', 'rejected', 'closed', 'filled'
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['position_id', 'status']);
            $table->index('requisition_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_vacancy_requisitions');
    }
};
