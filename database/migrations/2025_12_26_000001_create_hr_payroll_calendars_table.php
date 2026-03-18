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
        if (Schema::hasTable('hr_payroll_calendars')) {
            return;
        }

        Schema::create('hr_payroll_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->integer('calendar_year')->notNull();
            $table->integer('payroll_month')->notNull(); // 1-12
            $table->date('cut_off_date')->notNull(); // Last date for payroll data inclusion
            $table->date('pay_date')->notNull(); // Actual payment date
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'calendar_year', 'payroll_month'], 'payroll_cal_unique');
            $table->index(['calendar_year', 'payroll_month']);
            $table->index('is_locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_payroll_calendars');
    }
};

