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
        if (Schema::hasTable('hr_employee_compliance')) {
            return;
        }

        Schema::create('hr_employee_compliance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->string('compliance_type', 50)->notNull(); // 'paye', 'pension', 'nhif', 'wcf', 'sdl'
            $table->string('compliance_number', 100)->nullable();
            $table->boolean('is_valid')->default(false);
            $table->date('expiry_date')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'compliance_type']);
            $table->index('is_valid');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_compliance');
    }
};
