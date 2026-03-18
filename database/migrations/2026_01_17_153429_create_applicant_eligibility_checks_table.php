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
        Schema::create('hr_applicant_eligibility_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('hr_applicants')->onDelete('cascade');
            $table->foreignId('eligibility_rule_id')->constrained('hr_eligibility_rules')->onDelete('cascade');
            $table->foreignId('vacancy_requisition_id')->nullable()->constrained('hr_vacancy_requisitions')->onDelete('cascade');
            $table->boolean('passed')->default(false);
            $table->text('reason')->nullable(); // Explanation of pass/fail
            $table->json('checked_value')->nullable(); // The actual value checked
            $table->json('expected_value')->nullable(); // The expected value from rule
            $table->timestamp('checked_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index('applicant_id');
            $table->index('eligibility_rule_id');
            $table->index(['applicant_id', 'vacancy_requisition_id'], 'app_elig_app_vr_idx');
            $table->index('passed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_applicant_eligibility_checks');
    }
};
