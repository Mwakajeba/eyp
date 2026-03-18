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
        Schema::create('hr_appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('cycle_id')->constrained('hr_appraisal_cycles')->onDelete('cascade');
            $table->foreignId('appraiser_id')->constrained('users')->onDelete('restrict'); // Line manager
            $table->decimal('self_assessment_score', 5, 2)->nullable();
            $table->decimal('manager_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('rating', 50)->nullable(); // 'excellent', 'good', 'average', 'needs_improvement'
            $table->string('status', 50)->default('draft'); // 'draft', 'submitted', 'approved', 'locked'
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'cycle_id']);
            $table->index(['cycle_id', 'status']);
            $table->index('appraiser_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_appraisals');
    }
};
