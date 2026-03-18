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
        if (!Schema::hasTable('hr_applicant_normalized_profiles')) {
            Schema::create('hr_applicant_normalized_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained('hr_applicants')->onDelete('cascade');
                
                // Standardized Education
                $table->string('education_level', 50)->nullable(); // certificate, diploma, degree, masters, phd
                $table->string('education_field', 255)->nullable();
                
                // Standardized Experience
                $table->decimal('years_of_experience', 5, 2)->default(0);
                $table->string('current_role', 255)->nullable();
                
                // Standardized Skills & Certifications
                $table->json('skills')->nullable();
                $table->json('certifications')->nullable();
                
                // Metadata for Hybrid Approach
                $table->decimal('ai_confidence_score', 5, 2)->default(0);
                $table->boolean('requires_hr_review')->default(false);
                $table->boolean('is_manually_overridden')->default(false);
                $table->foreignId('overridden_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('overridden_at')->nullable();
                $table->text('override_reason')->nullable();
                
                // Audit Log
                $table->json('normalization_log')->nullable(); // Track what was original vs what was normalized
                
                $table->timestamps();

                $table->index('applicant_id');
                $table->index('education_level');
                $table->index('requires_hr_review');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_applicant_normalized_profiles');
    }
};
