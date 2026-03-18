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
        if (Schema::hasTable('hr_holiday_calendar_dates')) {
            return;
        }

        Schema::create('hr_holiday_calendar_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained('hr_holiday_calendars')->onDelete('cascade');
            $table->date('holiday_date')->notNull();
            $table->string('holiday_name', 200)->notNull();
            $table->string('holiday_type', 50)->default('public'); // 'public', 'company', 'regional'
            $table->boolean('is_paid')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['calendar_id', 'holiday_date']);
            $table->index('holiday_date');
            $table->index('holiday_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_holiday_calendar_dates');
    }
};

