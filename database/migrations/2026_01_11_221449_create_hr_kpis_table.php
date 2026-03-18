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
        Schema::create('hr_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('kpi_code', 50)->unique();
            $table->string('kpi_name', 200);
            $table->text('description')->nullable();
            $table->text('measurement_criteria')->nullable();
            $table->decimal('weight_percent', 5, 2)->nullable();
            $table->decimal('target_value', 10, 2)->nullable();
            $table->string('scoring_method', 50)->default('numeric'); // 'numeric', 'rating_scale'
            $table->string('applicable_to', 50)->default('individual'); // 'company', 'department', 'position', 'individual'
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_kpis');
    }
};
