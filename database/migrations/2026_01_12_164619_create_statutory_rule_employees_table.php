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
        Schema::create('hr_statutory_rule_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statutory_rule_id')->constrained('hr_statutory_rules')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['statutory_rule_id', 'employee_id'], 'unique_rule_employee');
            $table->index('statutory_rule_id');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_statutory_rule_employees');
    }
};
