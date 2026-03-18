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
        if (Schema::hasTable('ecl_override')) {
            return;
        }

        Schema::create('ecl_override', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('investment_id')->nullable()->constrained('investment_master')->onDelete('cascade');
            $table->foreignId('ecl_calc_id')->nullable()->constrained('ecl_calc')->onDelete('cascade');
            
            // Override information
            $table->date('override_date')->index();
            $table->enum('override_type', ['PD', 'LGD', 'EAD', 'STAGE', 'ECL_AMOUNT', 'FULL'])->default('FULL');
            
            // Override values
            $table->decimal('pd_override', 10, 6)->nullable();
            $table->decimal('lgd_override', 10, 6)->nullable();
            $table->decimal('ead_override', 18, 2)->nullable();
            $table->integer('stage_override')->nullable();
            $table->decimal('ecl_amount_override', 18, 2)->nullable();
            
            // Reason and justification
            $table->text('override_reason')->nullable();
            $table->text('justification')->nullable();
            $table->json('supporting_documents')->nullable(); // References to attachments
            
            // Approval workflow
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Effective period
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'override_date']);
            $table->index(['investment_id', 'is_active']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecl_override');
    }
};
