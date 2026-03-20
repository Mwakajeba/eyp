<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_sub_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('project_activity_id')->constrained('project_activities')->cascadeOnDelete();
            $table->string('sub_activity_name');
            $table->foreignId('chart_account_id')->constrained('chart_accounts')->restrictOnDelete();
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'project_activity_id'], 'project_sub_activities_company_activity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_sub_activities');
    }
};