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
        if (Schema::hasTable('hr_position_assignments')) {
            return;
        }

        Schema::create('hr_position_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('position_id')->constrained('hr_positions')->onDelete('cascade');
            $table->date('effective_date')->notNull();
            $table->date('end_date')->nullable();
            $table->boolean('is_acting')->default(false);
            $table->decimal('acting_allowance_percent', 5, 2)->default(0);
            $table->timestamps();

            $table->index(['employee_id', 'effective_date']);
            $table->index(['position_id', 'effective_date']);
            $table->index('is_acting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_position_assignments');
    }
};
