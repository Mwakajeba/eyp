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
        Schema::create('investment_market_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained('investment_master')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Price information
            $table->date('price_date')->index();
            $table->decimal('market_price', 18, 6)->default(0); // Price per unit
            $table->decimal('bid_price', 18, 6)->nullable();
            $table->decimal('ask_price', 18, 6)->nullable();
            $table->decimal('mid_price', 18, 6)->nullable(); // (bid + ask) / 2
            
            // Source information
            $table->enum('price_source', ['BOT', 'DSE', 'BLOOMBERG', 'REUTERS', 'MANUAL', 'INTERNAL', 'OTHER'])->default('MANUAL');
            $table->string('source_reference', 200)->nullable(); // Reference number from source
            $table->string('source_url', 500)->nullable(); // URL if available
            
            // Additional market data
            $table->decimal('yield_rate', 18, 12)->nullable(); // Yield at this price
            $table->decimal('volume', 18, 6)->nullable(); // Trading volume
            $table->json('additional_data')->nullable(); // Additional market data (JSON)
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index(['investment_id', 'price_date']);
            $table->index(['company_id', 'price_date']);
            $table->index('price_source');
            $table->unique(['investment_id', 'price_date']); // One price per investment per day
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_market_price_history');
    }
};
