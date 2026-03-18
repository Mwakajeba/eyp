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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Annual, Sick, Maternity, Paternity, Compassionate, Unpaid, TOIL
            $table->string('code', 20)->nullable(); // AL, SL, ML, PL, etc.
            $table->text('description')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('allow_half_day')->default(true);
            $table->boolean('allow_hourly')->default(false);
            $table->boolean('allow_negative')->default(false); // allow go-below-zero
            $table->unsignedInteger('min_duration_hours')->default(4); // policy guard
            $table->unsignedInteger('max_consecutive_days')->nullable();
            $table->unsignedInteger('notice_days')->default(0);
            $table->unsignedInteger('doc_required_after_days')->nullable(); // e.g. sick >2 days
            $table->boolean('encashable')->default(false);
            $table->unsignedInteger('carryover_cap_days')->nullable();
            $table->date('carryover_expiry_date')->nullable(); // e.g., Mar 31
            $table->json('weekend_holiday_mode')->nullable(); // {"count_weekends": false, "count_public_holidays": false}
            $table->json('eligibility')->nullable(); // employment_type, tenure_months, grades, locations
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('annual_entitlement')->default(0); // Default annual days
            $table->enum('accrual_type', ['annual', 'monthly', 'none'])->default('annual');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};

