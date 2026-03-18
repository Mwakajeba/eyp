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
        if (Schema::hasTable('hr_work_schedules')) {
            return;
        }

        Schema::create('hr_work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('schedule_code', 50)->notNull();
            $table->string('schedule_name', 200)->notNull();
            $table->json('weekly_pattern')->nullable(); // {'monday': true, 'tuesday': true, ...}
            $table->decimal('standard_daily_hours', 4, 2)->default(8.00);
            $table->integer('break_duration_minutes')->default(60);
            $table->boolean('overtime_eligible')->default(true);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'schedule_code']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_work_schedules');
    }
};

