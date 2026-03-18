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
        Schema::create('hr_onboarding_record_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_record_id')->constrained('hr_onboarding_records')->onDelete('cascade');
            $table->foreignId('checklist_item_id')->constrained('hr_onboarding_checklist_items')->onDelete('cascade');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['onboarding_record_id', 'checklist_item_id'], 'onboarding_record_items_unique');
            $table->index(['onboarding_record_id', 'is_completed'], 'onboarding_record_items_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_onboarding_record_items');
    }
};
