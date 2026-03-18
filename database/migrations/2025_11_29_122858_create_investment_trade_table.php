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
        Schema::create('investment_trade', function (Blueprint $table) {
            $table->id('trade_id');
            $table->foreignId('investment_id')->nullable()->constrained('investment_master')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Trade details
            $table->enum('trade_type', ['PURCHASE', 'SALE', 'MATURITY', 'COUPON'])->default('PURCHASE');
            $table->date('trade_date');
            $table->date('settlement_date');
            $table->decimal('trade_price', 18, 6)->default(0);
            $table->decimal('trade_units', 18, 6)->default(0);
            $table->decimal('gross_amount', 18, 2)->default(0);
            $table->decimal('fees', 18, 2)->default(0);
            $table->decimal('tax_withheld', 18, 2)->default(0);
            $table->string('bank_ref', 100)->nullable();
            
            // Settlement status
            $table->enum('settlement_status', ['PENDING', 'INSTRUCTED', 'SETTLED', 'FAILED'])->default('PENDING');
            
            // GL posting
            $table->foreignId('posted_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['investment_id', 'trade_type']);
            $table->index(['company_id', 'trade_date']);
            $table->index('settlement_status');
            $table->index('settlement_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_trade');
    }
};
