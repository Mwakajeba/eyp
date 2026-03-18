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
        Schema::create('hr_appraisal_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('cycle_name', 200);
            $table->string('cycle_type', 50); // 'annual', 'semi_annual', 'quarterly', 'probation'
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 50)->default('draft'); // 'draft', 'active', 'completed', 'cancelled'
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_appraisal_cycles');
    }
};
