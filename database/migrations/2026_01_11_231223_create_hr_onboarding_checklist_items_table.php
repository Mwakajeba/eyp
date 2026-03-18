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
        Schema::create('hr_onboarding_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_checklist_id')->constrained('hr_onboarding_checklists')->onDelete('cascade');
            $table->string('item_title', 200);
            $table->text('item_description')->nullable();
            $table->string('item_type', 50)->default('task'); // 'task', 'document_upload', 'policy_acknowledgment', 'system_access'
            $table->boolean('is_mandatory')->default(true);
            $table->integer('sequence_order')->default(0);
            $table->timestamps();

            $table->index(['onboarding_checklist_id', 'sequence_order'], 'onboarding_items_checklist_seq_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_onboarding_checklist_items');
    }
};
