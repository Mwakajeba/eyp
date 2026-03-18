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
        Schema::create('hr_interview_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('hr_applicants')->onDelete('cascade');
            $table->foreignId('vacancy_requisition_id')->nullable()->constrained('hr_vacancy_requisitions')->onDelete('set null');
            $table->string('interview_type', 50); // 'phone', 'video', 'in_person', 'panel'
            $table->string('round_number', 10)->default('1'); // '1', '2', '3', 'final'
            $table->date('interview_date');
            $table->time('interview_time');
            $table->string('location', 200)->nullable();
            $table->text('meeting_link')->nullable();
            $table->json('interviewers')->nullable(); // Array of user IDs
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->string('recommendation', 50)->nullable(); // 'hire', 'maybe', 'reject', 'next_round'
            $table->foreignId('interviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['applicant_id', 'interview_date']);
            $table->index(['vacancy_requisition_id', 'interview_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_interview_records');
    }
};
