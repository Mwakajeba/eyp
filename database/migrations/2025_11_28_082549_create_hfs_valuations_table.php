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
        Schema::create('hfs_valuations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hfs_id');
            $table->date('valuation_date');
            
            // Fair value and costs
            $table->decimal('fair_value', 18, 2)->default(0);
            $table->decimal('costs_to_sell', 18, 2)->default(0);
            $table->decimal('fv_less_costs', 18, 2)->default(0); // Computed: fair_value - costs_to_sell
            
            // Carrying amount at valuation date
            $table->decimal('carrying_amount', 18, 2)->default(0);
            
            // Impairment calculation
            $table->decimal('impairment_amount', 18, 2)->default(0); // If fv_less_costs < carrying_amount
            $table->boolean('is_reversal')->default(false); // True if this is a reversal of previous impairment
            $table->decimal('original_carrying_before_impairment', 18, 2)->nullable(); // For reversal limit check
            
            // Journal reference
            $table->unsignedBigInteger('impairment_journal_id')->nullable();
            $table->boolean('gl_posted')->default(false);
            $table->dateTime('gl_posted_at')->nullable();
            
            // Valuator information
            $table->string('valuator_name')->nullable();
            $table->string('valuator_license')->nullable();
            $table->string('valuator_company')->nullable();
            $table->string('report_ref')->nullable();
            $table->string('valuation_report_path')->nullable();
            
            // Override information (if manual override)
            $table->boolean('is_override')->default(false);
            $table->text('override_reason')->nullable();
            $table->unsignedBigInteger('override_approved_by')->nullable();
            $table->dateTime('override_approved_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['hfs_id']);
            $table->index(['valuation_date']);
            $table->index(['gl_posted']);
            
            // Foreign keys
            $table->foreign('hfs_id')->references('id')->on('hfs_requests')->onDelete('cascade');
            $table->foreign('impairment_journal_id')->references('id')->on('journals')->onDelete('set null');
            $table->foreign('override_approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hfs_valuations');
    }
};
