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
        Schema::create('asset_deferred_tax', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Tax period
            $table->date('tax_period_start')->index();
            $table->date('tax_period_end')->index();
            $table->integer('tax_year')->index();
            
            // Temporary differences
            $table->decimal('tax_base_carrying_amount', 18, 2)->default(0)->comment('Tax WDV (tax base)');
            $table->decimal('accounting_carrying_amount', 18, 2)->default(0)->comment('Accounting NBV (book carrying amount)');
            $table->decimal('temporary_difference', 18, 2)->default(0)->comment('NBV_book - WDV_tax');
            
            // Deferred tax calculation
            $table->decimal('tax_rate', 10, 6)->default(30)->comment('Corporate tax rate (%)');
            $table->decimal('deferred_tax_asset', 18, 2)->default(0)->comment('DTA (if accounting < tax)');
            $table->decimal('deferred_tax_liability', 18, 2)->default(0)->comment('DTL (if accounting > tax)');
            $table->decimal('net_deferred_tax', 18, 2)->default(0)->comment('Net DTA/DTL');
            
            // Movement tracking
            $table->decimal('opening_balance', 18, 2)->default(0)->comment('Opening DTL/DTA balance');
            $table->decimal('movement', 18, 2)->default(0)->comment('Period movement');
            $table->decimal('closing_balance', 18, 2)->default(0)->comment('Closing DTL/DTA balance');
            
            // Source of temporary difference
            $table->enum('difference_type', ['DEPRECIATION', 'REVALUATION', 'IMPAIRMENT', 'DISPOSAL', 'CAPITALIZATION', 'OTHER'])->default('DEPRECIATION');
            $table->text('difference_description')->nullable();
            
            // GL Posting
            $table->unsignedBigInteger('posted_journal_id')->nullable()->comment('Link to journal entry if posted to GL');
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'tax_year']);
            $table->index(['company_id', 'tax_year']);
            $table->index('is_posted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_deferred_tax');
    }
};
