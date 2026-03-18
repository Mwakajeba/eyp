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
        Schema::create('rental_accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('rental_income_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->foreignId('service_income_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->foreignId('deposits_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->foreignId('expenses_account_id')->nullable()->constrained('chart_accounts')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['company_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_accounting_settings');
    }
};
