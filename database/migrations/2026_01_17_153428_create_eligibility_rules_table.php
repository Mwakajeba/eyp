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
        Schema::create('hr_eligibility_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('position_id')->nullable()->constrained('hr_positions')->onDelete('cascade');
            $table->foreignId('vacancy_requisition_id')->nullable()->constrained('hr_vacancy_requisitions')->onDelete('cascade');
            $table->enum('rule_type', ['education', 'experience', 'certification', 'skill', 'safeguarding', 'age', 'other'])->default('other');
            $table->enum('rule_operator', ['equals', 'greater_than', 'less_than', 'contains', 'in', 'not_in', 'between'])->default('equals');
            $table->json('rule_value')->nullable(); // Stores the value(s) to compare against
            $table->text('rule_description')->nullable(); // Human-readable description
            $table->boolean('is_mandatory')->default(true); // If false, rule failure doesn't disqualify
            $table->integer('priority')->default(0); // Higher priority rules checked first
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'position_id']);
            $table->index(['company_id', 'vacancy_requisition_id']);
            $table->index('rule_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_eligibility_rules');
    }
};
