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
        if (Schema::hasTable('hr_pay_groups')) {
            return;
        }

        Schema::create('hr_pay_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('pay_group_code', 50)->notNull();
            $table->string('pay_group_name', 200)->notNull();
            $table->text('description')->nullable();
            $table->string('payment_frequency', 50)->default('monthly'); // 'monthly', 'daily', 'weekly', 'bi-weekly'
            $table->integer('cut_off_day')->nullable(); // Day of month for cut-off (e.g., 25)
            $table->integer('pay_day')->nullable(); // Day of month for payment (e.g., 28)
            $table->boolean('auto_adjust_weekends')->default(true); // Adjust for weekends/holidays
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'pay_group_code'], 'pay_group_code_unique');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_pay_groups');
    }
};

