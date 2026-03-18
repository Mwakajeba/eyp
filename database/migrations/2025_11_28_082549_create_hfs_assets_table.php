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
        Schema::create('hfs_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hfs_id');
            
            // Asset reference (polymorphic to support different asset types)
            $table->unsignedBigInteger('asset_id')->nullable(); // For fixed assets
            $table->string('asset_type')->default('PPE'); // PPE, INVENTORY, ROU, INVEST_PROP, OTHER
            $table->string('asset_reference')->nullable(); // For non-fixed assets or external references
            
            // Original accounting information at reclassification
            $table->unsignedBigInteger('original_account_id'); // Original asset account
            $table->decimal('carrying_amount_at_reclass', 18, 2)->default(0);
            $table->decimal('accumulated_depreciation_at_reclass', 18, 2)->default(0);
            $table->decimal('accumulated_impairment_at_reclass', 18, 2)->default(0);
            $table->decimal('asset_cost_at_reclass', 18, 2)->default(0);
            
            // Depreciation control
            $table->boolean('depreciation_stopped')->default(false);
            $table->date('reclassified_date')->nullable();
            
            // Currency and amounts
            $table->string('book_currency', 3)->default('USD');
            $table->decimal('book_currency_amount', 18, 2)->default(0);
            $table->string('local_currency', 3)->default('USD');
            $table->decimal('book_local_amount', 18, 2)->default(0);
            $table->decimal('book_fx_rate', 10, 6)->default(1);
            
            // Current carrying amount (after impairments/reversals)
            $table->decimal('current_carrying_amount', 18, 2)->default(0);
            
            // Status
            $table->enum('status', ['pending_reclass', 'classified', 'sold', 'cancelled'])->default('pending_reclass');
            
            // Collateral/pledge information
            $table->boolean('is_pledged')->default(false);
            $table->text('pledge_details')->nullable();
            $table->boolean('bank_consent_obtained')->default(false);
            $table->date('bank_consent_date')->nullable();
            $table->string('bank_consent_ref')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['hfs_id']);
            $table->index(['asset_id', 'asset_type']);
            $table->index(['status']);
            $table->index(['reclassified_date']);
            
            // Foreign keys
            $table->foreign('hfs_id')->references('id')->on('hfs_requests')->onDelete('cascade');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('original_account_id')->references('id')->on('chart_accounts')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hfs_assets');
    }
};
