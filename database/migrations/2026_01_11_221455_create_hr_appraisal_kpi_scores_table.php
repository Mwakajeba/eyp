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
        Schema::create('hr_appraisal_kpi_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_id')->constrained('hr_appraisals')->onDelete('cascade');
            $table->foreignId('kpi_id')->constrained('hr_kpis')->onDelete('cascade');
            $table->decimal('self_score', 5, 2)->nullable();
            $table->decimal('manager_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['appraisal_id', 'kpi_id']);
            $table->index('kpi_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_appraisal_kpi_scores');
    }
};
