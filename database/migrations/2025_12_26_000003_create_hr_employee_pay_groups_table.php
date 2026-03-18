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
        if (Schema::hasTable('hr_employee_pay_groups')) {
            return;
        }

        Schema::create('hr_employee_pay_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('pay_group_id')->constrained('hr_pay_groups')->onDelete('cascade');
            $table->date('effective_date')->notNull();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date']);
            $table->index('pay_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_pay_groups');
    }
};

