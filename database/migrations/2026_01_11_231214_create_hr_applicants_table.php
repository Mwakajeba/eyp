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
        Schema::create('hr_applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('vacancy_requisition_id')->nullable()->constrained('hr_vacancy_requisitions')->onDelete('set null');
            $table->string('application_number', 50)->unique();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('email', 100);
            $table->string('phone_number', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('qualification', 200)->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('status', 50)->default('applied'); // 'applied', 'screening', 'interview', 'offered', 'hired', 'rejected', 'withdrawn'
            $table->foreignId('converted_to_employee_id')->nullable()->constrained('hr_employees')->onDelete('set null');
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['vacancy_requisition_id', 'status']);
            $table->index('application_number');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_applicants');
    }
};
