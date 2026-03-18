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
        Schema::create('hfs_disposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hfs_id');
            $table->date('disposal_date');
            
            // Sale information
            $table->decimal('sale_proceeds', 18, 2)->default(0);
            $table->string('sale_currency', 3)->default('USD');
            $table->decimal('currency_rate', 10, 6)->default(1);
            $table->decimal('costs_sold', 18, 2)->default(0); // Actual costs incurred on sale
            
            // Carrying amount at disposal
            $table->decimal('carrying_amount_at_disposal', 18, 2)->default(0);
            $table->decimal('accumulated_impairment_at_disposal', 18, 2)->default(0);
            
            // Gain/Loss calculation
            $table->decimal('gain_loss_amount', 18, 2)->default(0);
            // Formula: gain_loss = sale_proceeds - carrying_amount_at_disposal - costs_sold
            
            // Buyer information
            $table->string('buyer_name')->nullable();
            $table->string('buyer_contact')->nullable();
            $table->text('buyer_address')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('settlement_reference')->nullable();
            
            // Bank account (if proceeds received)
            $table->unsignedBigInteger('bank_account_id')->nullable();
            
            // VAT and Tax
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('withholding_tax', 18, 2)->default(0);
            
            // Journal reference
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->boolean('gl_posted')->default(false);
            $table->dateTime('gl_posted_at')->nullable();
            
            // Partial sale flag
            $table->boolean('is_partial_sale')->default(false);
            $table->decimal('partial_sale_percentage', 5, 2)->nullable(); // 0-100
            
            // Notes
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['hfs_id']);
            $table->index(['disposal_date']);
            $table->index(['gl_posted']);
            
            // Foreign keys
            $table->foreign('hfs_id')->references('id')->on('hfs_requests')->onDelete('cascade');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('set null');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hfs_disposals');
    }
};
