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
        if (Schema::hasTable('hr_job_grades')) {
            return;
        }

        Schema::create('hr_job_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('grade_code', 20)->notNull();
            $table->string('grade_name', 100)->notNull();
            $table->decimal('minimum_salary', 15, 2)->nullable();
            $table->decimal('midpoint_salary', 15, 2)->nullable();
            $table->decimal('maximum_salary', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'grade_code']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_job_grades');
    }
};
