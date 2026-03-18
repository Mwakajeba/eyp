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
        Schema::create('hr_confirmation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->date('probation_start_date');
            $table->date('probation_end_date');
            $table->date('review_date')->nullable();
            $table->text('performance_summary')->nullable();
            $table->text('recommendation')->nullable();
            $table->string('recommendation_type', 50)->nullable(); // 'confirm', 'extend', 'terminate'
            $table->integer('extension_months')->nullable();
            $table->string('status', 50)->default('pending'); // 'pending', 'manager_review', 'hr_review', 'approved', 'rejected', 'extended'
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('reviewed_by_manager')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('manager_reviewed_at')->nullable();
            $table->foreignId('reviewed_by_hr')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('hr_reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->date('confirmation_effective_date')->nullable();
            $table->decimal('salary_adjustment_amount', 15, 2)->nullable();
            $table->decimal('confirmation_bonus', 15, 2)->nullable();
            $table->boolean('salary_adjusted')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index('probation_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_confirmation_requests');
    }
};
