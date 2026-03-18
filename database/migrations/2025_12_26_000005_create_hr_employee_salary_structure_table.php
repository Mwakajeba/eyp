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
        if (Schema::hasTable('hr_employee_salary_structure')) {
            return;
        }

        Schema::create('hr_employee_salary_structure', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('component_id')->constrained('hr_salary_components')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->nullable(); // For fixed amount
            $table->decimal('percentage', 5, 2)->nullable(); // For percentage-based (e.g., 10.50%)
            $table->date('effective_date')->notNull();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date']);
            $table->index('component_id');
            $table->index(['employee_id', 'component_id', 'effective_date'], 'emp_comp_eff_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_salary_structure');
    }
};

