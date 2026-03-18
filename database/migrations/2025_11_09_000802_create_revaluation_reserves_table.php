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
        Schema::create('revaluation_reserves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('revaluation_id')->nullable();
            $table->unsignedBigInteger('impairment_id')->nullable();
            $table->unsignedBigInteger('reserve_account_id'); // Chart of account ID for revaluation reserve
            
            // Movement details
            $table->date('movement_date');
            $table->enum('movement_type', ['opening', 'revaluation_increase', 'revaluation_decrease', 'impairment_charge', 'impairment_reversal', 'transfer_to_retained_earnings', 'disposal_transfer'])->default('revaluation_increase');
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('balance_after', 18, 2)->default(0);
            
            // Transfer details (for partial transfers to retained earnings)
            $table->unsignedBigInteger('retained_earnings_account_id')->nullable();
            $table->decimal('transfer_amount', 18, 2)->default(0);
            $table->text('transfer_reason')->nullable();
            
            // Reference information
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'branch_id']);
            $table->index(['asset_id']);
            $table->index(['movement_date']);
            $table->index(['movement_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revaluation_reserves');
    }
};
