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
        Schema::create('investment_amort_line', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained('investment_master')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('days');
            $table->decimal('opening_carrying_amount', 18, 2);
            $table->decimal('interest_income', 18, 2);
            $table->decimal('cash_flow', 18, 2);
            $table->decimal('amortization', 18, 2);
            $table->decimal('closing_carrying_amount', 18, 2);
            $table->decimal('eir_rate', 18, 12);
            $table->boolean('posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->timestamps();

            $table->index(['investment_id', 'period_end']);
            $table->index(['investment_id', 'posted', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_amort_line');
    }
};
