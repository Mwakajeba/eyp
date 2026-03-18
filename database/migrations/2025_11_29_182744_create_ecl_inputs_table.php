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
        if (Schema::hasTable('ecl_inputs')) {
            return;
        }

        Schema::create('ecl_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('investment_id')->nullable()->constrained('investment_master')->onDelete('cascade');
            
            // Snapshot information
            $table->date('snapshot_date')->index();
            $table->string('snapshot_type', 50)->default('FULL'); // FULL, INCREMENTAL
            
            // Exposure data
            $table->decimal('exposure_amount', 18, 2)->default(0); // EAD
            $table->decimal('carrying_amount', 18, 2)->default(0);
            $table->integer('days_past_due')->default(0);
            $table->integer('stage')->default(1); // IFRS 9 stage
            
            // Credit data
            $table->decimal('pd', 10, 6)->default(0); // Probability of default
            $table->decimal('lgd', 10, 6)->default(45); // Loss given default
            $table->string('credit_rating', 50)->nullable();
            $table->string('credit_grade', 50)->nullable();
            
            // Additional data
            $table->json('additional_data')->nullable(); // Flexible JSON for additional fields
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'snapshot_date']);
            $table->index(['investment_id', 'snapshot_date']);
            $table->index('stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecl_inputs');
    }
};
