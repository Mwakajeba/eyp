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
        if (Schema::hasTable('hr_salary_components')) {
            return;
        }

        Schema::create('hr_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('component_code', 50)->notNull();
            $table->string('component_name', 200)->notNull();
            $table->enum('component_type', ['earning', 'deduction'])->notNull();
            $table->text('description')->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_pensionable')->default(false);
            $table->boolean('is_nhif_applicable')->default(true);
            $table->enum('calculation_type', ['fixed', 'formula', 'percentage'])->default('fixed');
            $table->text('calculation_formula')->nullable(); // For formula-based calculations
            $table->decimal('ceiling_amount', 15, 2)->nullable(); // Maximum amount
            $table->decimal('floor_amount', 15, 2)->nullable(); // Minimum amount
            $table->integer('display_order')->default(0); // For display ordering
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'component_code'], 'salary_component_code_unique');
            $table->index('component_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_salary_components');
    }
};

