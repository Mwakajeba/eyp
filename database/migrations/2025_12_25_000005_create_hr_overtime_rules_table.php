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
        if (Schema::hasTable('hr_overtime_rules')) {
            return;
        }

        Schema::create('hr_overtime_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('grade_id')->nullable()->constrained('hr_job_grades')->onDelete('set null');
            $table->string('day_type', 50)->notNull(); // 'weekday', 'weekend', 'holiday'
            $table->decimal('overtime_rate', 5, 2)->notNull(); // 1.50, 2.00, etc.
            $table->decimal('max_hours_per_day', 4, 2)->nullable();
            $table->boolean('requires_approval')->default(true);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'grade_id', 'day_type']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_overtime_rules');
    }
};

