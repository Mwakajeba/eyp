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
        Schema::create('hr_offer_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('hr_applicants')->onDelete('cascade');
            $table->foreignId('vacancy_requisition_id')->nullable()->constrained('hr_vacancy_requisitions')->onDelete('set null');
            $table->string('offer_number', 50)->unique();
            $table->string('offer_letter_path')->nullable();
            $table->decimal('offered_salary', 15, 2);
            $table->date('offer_date');
            $table->date('expiry_date');
            $table->date('proposed_start_date')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->string('status', 50)->default('draft'); // 'draft', 'pending_approval', 'approved', 'sent', 'accepted', 'rejected', 'expired', 'withdrawn'
            $table->foreignId('prepared_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('response_notes')->nullable();
            $table->timestamps();

            $table->index(['applicant_id', 'status']);
            $table->index(['vacancy_requisition_id', 'status']);
            $table->index('offer_number');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_offer_letters');
    }
};
