<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_external_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('employee_id')->constrained('hr_employees');
            $table->string('institution_name');
            $table->decimal('total_loan', 18, 2);
            $table->decimal('monthly_deduction', 18, 2);
            $table->date('date_end_of_loan')->nullable();
            $table->date('date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_external_loans');
    }
};


