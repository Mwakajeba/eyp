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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->decimal('opening_days', 8, 2)->default(0);
            $table->decimal('carried_over_days', 8, 2)->default(0);
            $table->decimal('accrued_days', 8, 2)->default(0);
            $table->decimal('taken_days', 8, 2)->default(0);
            $table->decimal('pending_hold_days', 8, 2)->default(0); // pending requests hold balance
            $table->decimal('expired_days', 8, 2)->default(0);
            $table->decimal('adjusted_days', 8, 2)->default(0);
            $table->date('as_of');
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'as_of'], 'unique_employee_type_asof');
            $table->index(['company_id', 'as_of']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};

