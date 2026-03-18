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
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('department_id')->nullable()->constrained('hr_departments');
            $table->foreignId('position_id')->nullable()->constrained('hr_positions');

            // Basic Information
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed']);

            // Location Information
            $table->string('country');
            $table->string('region');
            $table->string('district');
            $table->text('current_physical_location');

            // Contact Information
            $table->string('email')->unique();
            $table->string('phone_number');

            // Employment Information
            $table->decimal('basic_salary', 10, 2);
            $table->string('identity_document_type');
            $table->string('identity_number');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern']);
            $table->date('date_of_employment');
            $table->string('designation');
            $table->string('tin')->nullable();

            // Banking Information
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive', 'terminated', 'on_leave'])->default('active');

            // NHIF Information
            $table->boolean('has_nhif')->default(false);
            $table->decimal('nhif_employee_percent', 5, 2)->nullable();
            $table->decimal('nhif_employer_percent', 5, 2)->nullable();
            $table->string('nhif_member_number')->nullable();

            // Pension Information
            $table->boolean('has_pension')->default(false);
            $table->string('social_fund_type')->nullable();
            $table->string('social_fund_number')->nullable();
            $table->decimal('pension_employee_percent', 5, 2)->nullable();
            $table->decimal('pension_employer_percent', 5, 2)->nullable();

            // Trade Union Information
            $table->boolean('has_trade_union')->default(false);
            $table->string('trade_union_type')->nullable();
            $table->string('trade_union_category')->nullable();
            $table->decimal('trade_union_amount', 10, 2)->nullable();
            $table->decimal('trade_union_percent', 5, 2)->nullable();

            // Additional Benefits
            $table->boolean('has_wcf')->default(false);
            $table->boolean('has_heslb')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employees');
    }
};
