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
        Schema::create('hr_onboarding_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('onboarding_checklist_id')->constrained('hr_onboarding_checklists')->onDelete('restrict');
            $table->date('start_date');
            $table->date('completion_date')->nullable();
            $table->string('status', 50)->default('in_progress'); // 'in_progress', 'completed', 'on_hold'
            $table->integer('total_items')->default(0);
            $table->integer('completed_items')->default(0);
            $table->integer('progress_percent')->default(0);
            $table->boolean('payroll_eligible')->default(false);
            $table->timestamp('payroll_activated_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['onboarding_checklist_id', 'status']);
            $table->index('payroll_eligible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_onboarding_records');
    }
};
