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
        if (Schema::hasTable('hr_overtime_request_lines')) {
            return;
        }

        Schema::create('hr_overtime_request_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_request_id')->constrained('hr_overtime_requests')->onDelete('cascade');
            $table->decimal('overtime_hours', 4, 2)->notNull();
            $table->string('day_type', 50)->notNull(); // 'weekday', 'weekend', 'holiday'
            $table->decimal('overtime_rate', 5, 2)->notNull();
            $table->timestamps();

            $table->index('overtime_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_overtime_request_lines');
    }
};
