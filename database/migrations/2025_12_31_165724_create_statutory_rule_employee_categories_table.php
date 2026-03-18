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
        Schema::create('hr_statutory_rule_employee_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statutory_rule_id')->constrained('hr_statutory_rules')->onDelete('cascade');
            $table->string('category_type', 50); // 'employment_type', 'position', 'department', 'grade', 'custom'
            $table->string('category_value', 200); // The actual value (e.g., 'permanent', position_id, department_id, etc.)
            $table->string('category_label')->nullable(); // Human-readable label
            $table->timestamps();

            $table->index(['statutory_rule_id', 'category_type'], 'idx_stat_rule_cat');
            $table->unique(['statutory_rule_id', 'category_type', 'category_value'], 'unique_rule_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_statutory_rule_employee_categories');
    }
};
