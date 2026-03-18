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
        if (!Schema::hasTable('hr_salary_structure_templates')) {
        Schema::create('hr_salary_structure_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('template_name');
            $table->string('template_code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });
        }

        if (!Schema::hasTable('hr_salary_structure_template_components')) {
        Schema::create('hr_salary_structure_template_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('hr_salary_structure_templates')->onDelete('cascade');
            $table->foreignId('component_id')->constrained('hr_salary_components')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->unique(['template_id', 'component_id'], 'template_component_unique');
            $table->index('template_id');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_salary_structure_template_components');
        Schema::dropIfExists('hr_salary_structure_templates');
    }
};
