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
        Schema::create('hr_onboarding_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('checklist_name', 200);
            $table->text('description')->nullable();
            $table->string('applicable_to', 50)->default('all'); // 'all', 'department', 'position', 'role'
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->onDelete('set null');
            $table->foreignId('position_id')->nullable()->constrained('hr_positions')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['department_id', 'is_active']);
            $table->index(['position_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_onboarding_checklists');
    }
};
