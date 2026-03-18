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
        if (!Schema::hasTable('hr_vacancy_requisition_approvals')) {
            Schema::create('hr_vacancy_requisition_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vacancy_requisition_id')->constrained('hr_vacancy_requisitions')->cascadeOnDelete();
                $table->foreignId('approval_level_id')->nullable()->constrained('approval_levels')->nullOnDelete();
                $table->foreignId('approver_id')->constrained('users');
                $table->string('action')->comment('submitted, approved, rejected');
                $table->timestamp('action_at')->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();
                
                // Indexes with shorter names
                $table->index(['vacancy_requisition_id', 'approval_level_id'], 'hr_vac_req_app_level_idx');
                $table->index(['approver_id', 'action'], 'hr_vac_req_app_action_idx');
                $table->index('action', 'hr_vac_req_app_status_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_vacancy_requisition_approvals');
    }
};
