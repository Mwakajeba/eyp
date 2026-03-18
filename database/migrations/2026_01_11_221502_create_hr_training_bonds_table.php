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
        Schema::create('hr_training_bonds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('training_program_id')->constrained('hr_training_programs')->onDelete('cascade');
            $table->decimal('bond_amount', 15, 2);
            $table->integer('bond_period_months');
            $table->date('start_date');
            $table->date('end_date');
            $table->json('recovery_rules')->nullable();
            $table->string('status', 50)->default('active'); // 'active', 'fulfilled', 'recovered'
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['training_program_id', 'status']);
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_training_bonds');
    }
};
