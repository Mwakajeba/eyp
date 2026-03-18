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
        if (Schema::hasTable('hr_contracts')) {
            return;
        }

        Schema::create('hr_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->string('contract_type', 50)->notNull(); // 'permanent', 'fixed_term', 'probation', etc.
            $table->date('start_date')->notNull();
            $table->date('end_date')->nullable();
            $table->integer('working_hours_per_week')->default(40);
            $table->decimal('salary_reference', 15, 2)->nullable();
            $table->boolean('renewal_flag')->default(false);
            $table->string('status', 50)->default('active'); // 'active', 'expired', 'terminated'
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_contracts');
    }
};
